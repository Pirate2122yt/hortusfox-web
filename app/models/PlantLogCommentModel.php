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
