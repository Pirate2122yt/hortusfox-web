<?php

/**
 * Class PlantLogCommentModel
 *
 * Comments left on a plant journal entry from the public catalogue
 * (see PublicController). Anyone with the link can add one - there's
 * no account behind them - so they're always treated as untrusted
 * text: stored as plain text and rendered through the auto-escaping
 * {{ }} template syntax, never {!! !!}. Any signed-in user can remove
 * one, same as the rest of the app's single-tier permission model.
 */
class PlantLogCommentModel extends \Asatru\Database\Model {
    const MAX_NAME_LENGTH = 100;
    const MAX_COMMENT_LENGTH = 2000;

    /**
     * @param $logEntryId
     * @param $authorName
     * @param $comment
     * @return int
     * @throws \Exception
     */
    public static function addComment($logEntryId, $authorName, $comment)
    {
        try {
            $entry = PlantLogModel::raw('SELECT * FROM `PlantLogModel` WHERE id = ?', [$logEntryId])->first();
            if (!$entry) {
                throw new \Exception('Invalid journal entry: ' . $logEntryId);
            }

            $authorName = trim((string)$authorName);
            if (strlen($authorName) === 0) {
                $authorName = null;
            } else if (strlen($authorName) > static::MAX_NAME_LENGTH) {
                $authorName = substr($authorName, 0, static::MAX_NAME_LENGTH);
            }

            $comment = trim((string)$comment);
            if (strlen($comment) === 0) {
                throw new \Exception('A comment is required');
            }

            if (strlen($comment) > static::MAX_COMMENT_LENGTH) {
                $comment = substr($comment, 0, static::MAX_COMMENT_LENGTH);
            }

            static::raw('INSERT INTO `@THIS` (log_entry, author_name, comment) VALUES(?, ?, ?)', [
                $logEntryId, $authorName, $comment
            ]);

            $row = static::raw('SELECT * FROM `@THIS` ORDER BY id DESC LIMIT 1')->first();

            return $row->get('id');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $logEntryId
     * @return mixed
     * @throws \Exception
     */
    public static function getForEntry($logEntryId)
    {
        try {
            return static::raw('SELECT * FROM `@THIS` WHERE log_entry = ? ORDER BY id ASC', [$logEntryId]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $logEntryIds
     * @return array log_entry id => count
     * @throws \Exception
     */
    public static function getCountsForEntries($logEntryIds)
    {
        try {
            $counts = [];

            if (!is_array($logEntryIds) || count($logEntryIds) === 0) {
                return $counts;
            }

            $placeholders = implode(',', array_fill(0, count($logEntryIds), '?'));

            $rows = static::raw('SELECT log_entry, COUNT(*) as count FROM `@THIS` WHERE log_entry IN (' . $placeholders . ') GROUP BY log_entry', $logEntryIds);

            foreach ($rows as $row) {
                $counts[$row->get('log_entry')] = (int)$row->get('count');
            }

            return $counts;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return void
     * @throws \Exception
     */
    public static function removeComment($id)
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            static::raw('DELETE FROM `@THIS` WHERE id = ?', [$id]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Emails admin users when a new public comment is posted. These come
     * from anonymous visitors, so without this admins would only find out
     * by noticing the internal chat notification (itself only shown if
     * chat_system is enabled) or by happening to revisit the plant. A
     * failed send for one admin never blocks the others.
     *
     * By default every admin is emailed; the admin settings screen can
     * narrow this down to specific admin accounts (public_comment_notify_admin_ids) -
     * an empty selection there keeps the "notify everyone" default.
     *
     * @param $plant
     * @param $entry
     * @param $authorName
     * @param $comment
     * @return void
     * @throws \Exception
     */
    public static function notifyAdmins($plant, $entry, $authorName, $comment)
    {
        try {
            $selected_admin_ids_raw = trim((string)app('public_comment_notify_admin_ids', ''));
            $selected_admin_ids = ($selected_admin_ids_raw === '') ? [] : array_values(array_filter(array_map('intval', explode(',', $selected_admin_ids_raw))));

            $admins = UserModel::getAdmins($selected_admin_ids);

            $authorName = trim((string)$authorName);
            if ($authorName === '') {
                $authorName = __('app.public_comment_anonymous');
            }

            // Rendering emails in each admin's own language switches the
            // active locale, which also sets a cookie on the current HTTP
            // response - restore it afterwards rather than leaking the
            // last-emailed admin's language onto the visitor's browser.
            $original_lang = getLocale();

            try {
                foreach ($admins as $admin) {
                    if (!$admin->get('email')) {
                        continue;
                    }

                    $lang = $admin->get('lang');
                    if ($lang === null) {
                        $lang = env('APP_LANG', 'en');
                    }

                    setLanguage($lang);

                    $mailobj = new Asatru\SMTPMailer\SMTPMailer();
                    $mailobj->setRecipient($admin->get('email'));
                    $mailobj->setSubject('[' . __('app.mail_info_plant_comment_new') . '] ' . $plant->get('name'));
                    $mailobj->setView('mail/mail_layout', [['mail_content', 'mail/plant_comment_new']], ['plant' => $plant, 'entry' => $entry, 'authorName' => $authorName, 'comment' => $comment]);
                    $mailobj->setProperties(mail_properties());
                    $mailobj->send();
                }
            } finally {
                setLanguage($original_lang);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $logEntryId
     * @return void
     * @throws \Exception
     */
    public static function clearForEntry($logEntryId)
    {
        try {
            static::raw('DELETE FROM `@THIS` WHERE log_entry = ?', [$logEntryId]);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
