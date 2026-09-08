<?php

/**
 * Class FeatureRequestVoteModel
 *
 * One row per (request, user) upvote on a FeatureRequestModel entry.
 */
class FeatureRequestVoteModel extends \Asatru\Database\Model {
    /**
     * @param $requestId
     * @param $userId
     * @return void
     * @throws \Exception
     */
    public static function addVote($requestId, $userId)
    {
        try {
            if (static::hasVoted($requestId, $userId)) {
                return;
            }

            static::raw('INSERT INTO `@THIS` (request_id, user_id) VALUES(?, ?)', [$requestId, $userId]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $requestId
     * @param $userId
     * @return void
     * @throws \Exception
     */
    public static function removeVote($requestId, $userId)
    {
        try {
            static::raw('DELETE FROM `@THIS` WHERE request_id = ? AND user_id = ?', [$requestId, $userId]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $requestId
     * @param $userId
     * @return bool
     * @throws \Exception
     */
    public static function hasVoted($requestId, $userId)
    {
        try {
            $item = static::raw('SELECT * FROM `@THIS` WHERE request_id = ? AND user_id = ?', [$requestId, $userId])->first();

            return $item !== null;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $requestId
     * @return int
     * @throws \Exception
     */
    public static function getCount($requestId)
    {
        try {
            $row = static::raw('SELECT COUNT(*) as count FROM `@THIS` WHERE request_id = ?', [$requestId])->first();

            return ($row) ? (int)$row->get('count') : 0;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $requestId
     * @return void
     * @throws \Exception
     */
    public static function clearForRequest($requestId)
    {
        try {
            static::raw('DELETE FROM `@THIS` WHERE request_id = ?', [$requestId]);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
