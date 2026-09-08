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

            return ($item) ? $item->get('id') : 0;
        } catch (\Exception $e) {
            throw $e;
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
}
