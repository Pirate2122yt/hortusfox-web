<?php

/**
 * Class FeatureRequestModel
 *
 * Management of user-submitted feature requests. Anyone signed in can
 * view the board and submit a request; only the original requester or
 * an admin may edit or remove one, and only an admin may change its
 * status. Other signed-in users may upvote a request (see
 * FeatureRequestVoteModel) to help surface what's wanted most.
 */
class FeatureRequestModel extends \Asatru\Database\Model {
    const STATUS_OPEN = 'open';
    const STATUS_PLANNED = 'planned';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_ADDED = 'added';
    const STATUS_DECLINED = 'declined';

    static $statuses = [
        self::STATUS_OPEN,
        self::STATUS_PLANNED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_ADDED,
        self::STATUS_DECLINED
    ];

    /**
     * @param $status
     * @return void
     * @throws \Exception
     */
    public static function validateStatus($status)
    {
        if (!in_array($status, static::$statuses)) {
            throw new \Exception('Invalid status: ' . $status);
        }
    }

    /**
     * @param $title
     * @param $description
     * @return int
     * @throws \Exception
     */
    public static function addRequest($title, $description = '')
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            if ((!is_string($title)) || (strlen(trim($title)) === 0)) {
                throw new \Exception('A title is required');
            }

            static::raw('INSERT INTO `@THIS` (user, title, description, status) VALUES(?, ?, ?, ?)', [
                $user->get('id'), trim($title), $description ?? '', self::STATUS_OPEN
            ]);

            $item = static::raw('SELECT * FROM `@THIS` ORDER BY id DESC LIMIT 1')->first();

            if ($item) {
                try {
                    static::notifyNewRequest($item, $user);
                } catch (\Exception $e) {
                    // A failed notification email shouldn't turn a successfully
                    // saved feature request into an error for the requester -
                    // but it shouldn't vanish silently either.
                    addLog(ASATRU_LOG_ERROR, 'Failed to send feature request notification email: ' . $e->getMessage());
                }
            }

            return ($item) ? $item->get('id') : 0;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Emails the admin-configured recipient about a newly submitted
     * feature request, if notifications are enabled. Does nothing if no
     * recipient is configured, the configured user no longer exists, or
     * the recipient is the same person who just submitted the request.
     *
     * @param $item
     * @param $requester
     * @return void
     * @throws \Exception
     */
    private static function notifyNewRequest($item, $requester)
    {
        $notify_user_id = app('feature_request_notify_user');
        if (!$notify_user_id) {
            return;
        }

        if ((int)$notify_user_id === (int)$requester->get('id')) {
            return;
        }

        $notify_user = UserModel::getUserById($notify_user_id);
        if ((!$notify_user) || (!$notify_user->get('email'))) {
            return;
        }

        // Rendering the email in the recipient's language switches the
        // active locale, which also sets a cookie on the current HTTP
        // response - fine for a cronjob response nobody reads, but this
        // runs inside the requester's own request, so restore it
        // afterwards rather than leaking the recipient's language
        // preference onto the requester's browser.
        $original_lang = getLocale();

        try {
            $lang = $notify_user->get('lang');
            if ($lang === null) {
                $lang = env('APP_LANG', 'en');
            }

            setLanguage($lang);

            $mailobj = new Asatru\SMTPMailer\SMTPMailer();
            $mailobj->setRecipient($notify_user->get('email'));
            $mailobj->setSubject('[' . __('app.mail_info_feature_request_new') . '] ' . $item->get('title'));
            $mailobj->setView('mail/mail_layout', [['mail_content', 'mail/feature_request_new']], ['item' => $item, 'requester' => $requester]);
            $mailobj->setProperties(mail_properties());
            $mailobj->send();
        } finally {
            setLanguage($original_lang);
        }
    }

    /**
     * Only the original requester or an admin may edit a request's
     * title/description. Changing the status is a separate, admin-only
     * operation (see setStatus).
     *
     * @param $id
     * @param $title
     * @param $description
     * @return void
     * @throws \Exception
     */
    public static function editRequest($id, $title, $description = '')
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            $item = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
            if (!$item) {
                throw new \Exception('Invalid request: ' . $id);
            }

            if (($item->get('user') != $user->get('id')) && (!$user->get('admin'))) {
                throw new \Exception('Not allowed to edit this request');
            }

            if ((!is_string($title)) || (strlen(trim($title)) === 0)) {
                throw new \Exception('A title is required');
            }

            static::raw('UPDATE `@THIS` SET title = ?, description = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [
                trim($title), $description ?? '', $item->get('id')
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return void
     * @throws \Exception
     */
    public static function removeRequest($id)
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            $item = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
            if (!$item) {
                throw new \Exception('Invalid request: ' . $id);
            }

            if (($item->get('user') != $user->get('id')) && (!$user->get('admin'))) {
                throw new \Exception('Not allowed to remove this request');
            }

            FeatureRequestVoteModel::clearForRequest($item->get('id'));

            static::raw('DELETE FROM `@THIS` WHERE id = ?', [$item->get('id')]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @param $status
     * @return void
     * @throws \Exception
     */
    public static function setStatus($id, $status)
    {
        try {
            $user = UserModel::getAuthUser();
            if ((!$user) || (!$user->get('admin'))) {
                throw new \Exception('Not allowed to change the status of a request');
            }

            static::validateStatus($status);

            $item = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
            if (!$item) {
                throw new \Exception('Invalid request: ' . $id);
            }

            static::raw('UPDATE `@THIS` SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [
                $status, $item->get('id')
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Toggles the current user's upvote on a request and returns the
     * resulting vote count.
     *
     * @param $id
     * @return int
     * @throws \Exception
     */
    public static function toggleVote($id)
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            $item = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
            if (!$item) {
                throw new \Exception('Invalid request: ' . $id);
            }

            if (FeatureRequestVoteModel::hasVoted($item->get('id'), $user->get('id'))) {
                FeatureRequestVoteModel::removeVote($item->get('id'), $user->get('id'));
            } else {
                FeatureRequestVoteModel::addVote($item->get('id'), $user->get('id'));
            }

            return FeatureRequestVoteModel::getCount($item->get('id'));
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public static function getById($id)
    {
        try {
            return static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Lists all requests, most-upvoted first (ties broken by newest).
     *
     * @param $statusFilter
     * @return mixed
     * @throws \Exception
     */
    public static function getAll($statusFilter = null)
    {
        try {
            if ((is_string($statusFilter)) && (strlen($statusFilter) > 0)) {
                static::validateStatus($statusFilter);

                return static::raw('SELECT fr.*, (SELECT COUNT(*) FROM `FeatureRequestVoteModel` v WHERE v.request_id = fr.id) AS vote_count FROM `@THIS` fr WHERE fr.status = ? ORDER BY vote_count DESC, fr.created_at DESC', [$statusFilter]);
            }

            return static::raw('SELECT fr.*, (SELECT COUNT(*) FROM `FeatureRequestVoteModel` v WHERE v.request_id = fr.id) AS vote_count FROM `@THIS` fr ORDER BY vote_count DESC, fr.created_at DESC');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * The public changelog - every request that has actually shipped
     * (status "added"), newest first. Ordered by updated_at rather than
     * created_at because setStatus() bumps updated_at whenever a
     * request's status changes, so it reflects when the request was
     * added rather than when it was first suggested.
     *
     * @return mixed
     * @throws \Exception
     */
    public static function getChangelog()
    {
        try {
            return static::raw('SELECT * FROM `@THIS` WHERE status = ? ORDER BY updated_at DESC', [self::STATUS_ADDED]);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
