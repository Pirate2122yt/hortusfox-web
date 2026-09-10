<?php

/**
 * Class UserPreferredLocationModel
 *
 * Lets a user narrow down which Locations they want push notifications
 * about (Tasks and Plant care reminders tied to a Plant's Location). A
 * user who hasn't picked any preferred Locations gets everything - the
 * filter only kicks in once they've explicitly opted into one or more.
 */
class UserPreferredLocationModel extends \Asatru\Database\Model {
    /**
     * @param $userId
     * @return int[]
     * @throws \Exception
     */
    public static function getLocationIdsForUser($userId)
    {
        try {
            $rows = static::raw('SELECT location FROM `@THIS` WHERE user = ?', [$userId]);

            $ids = [];
            foreach ($rows as $row) {
                $ids[] = (int)$row->get('location');
            }

            return $ids;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Replaces a user's whole set of preferred Locations with $locationIds.
     *
     * @param $userId
     * @param $locationIds
     * @return void
     * @throws \Exception
     */
    public static function setForUser($userId, $locationIds)
    {
        try {
            static::raw('DELETE FROM `@THIS` WHERE user = ?', [$userId]);

            if (!is_array($locationIds)) {
                return;
            }

            foreach ($locationIds as $locationId) {
                if (is_numeric($locationId)) {
                    static::raw('INSERT INTO `@THIS` (user, location) VALUES(?, ?)', [$userId, (int)$locationId]);
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Whether a push notification about $locationId should reach $userId,
     * per that user's preferred-Locations filter. A user with no
     * preference set (the default) wants every Location.
     *
     * @param $userId
     * @param $locationId
     * @return bool
     * @throws \Exception
     */
    public static function wantsLocation($userId, $locationId)
    {
        try {
            $ids = static::getLocationIdsForUser($userId);

            if (count($ids) === 0) {
                return true;
            }

            return in_array((int)$locationId, $ids, true);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
