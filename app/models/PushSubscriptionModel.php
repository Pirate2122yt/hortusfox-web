<?php

/**
 * Class PushSubscriptionModel
 *
 * Stores browser Web Push subscriptions per user, so PushNotificationModule
 * can later deliver notifications to every device a user has subscribed
 * from. There's no DB-level unique constraint on `endpoint` (endpoints can
 * be long and MySQL/MariaDB key-length limits vary by server config), so
 * de-duplication is handled here in PHP via a select-then-insert-or-update.
 */
class PushSubscriptionModel extends \Asatru\Database\Model {
    /**
     * Creates or updates a subscription for the given endpoint. Re-subscribing
     * the same endpoint (e.g. the browser rotated its keys) simply refreshes
     * the stored keys rather than creating a duplicate row.
     *
     * @param $userId
     * @param $endpoint
     * @param $p256dh
     * @param $authToken
     * @param $userAgent
     * @return void
     * @throws \Exception
     */
    public static function subscribe($userId, $endpoint, $p256dh, $authToken, $userAgent = null)
    {
        try {
            $row = static::raw('SELECT * FROM `@THIS` WHERE endpoint = ?', [$endpoint])->first();

            if ($row) {
                static::raw('UPDATE `@THIS` SET user = ?, p256dh = ?, auth_token = ?, user_agent = ? WHERE id = ?', [$userId, $p256dh, $authToken, $userAgent, $row->get('id')]);
            } else {
                static::raw('INSERT INTO `@THIS` (user, endpoint, p256dh, auth_token, user_agent) VALUES(?, ?, ?, ?, ?)', [$userId, $endpoint, $p256dh, $authToken, $userAgent]);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $endpoint
     * @return void
     * @throws \Exception
     */
    public static function removeByEndpoint($endpoint)
    {
        try {
            static::raw('DELETE FROM `@THIS` WHERE endpoint = ?', [$endpoint]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $userId
     * @return \Asatru\Database\Collection
     * @throws \Exception
     */
    public static function getForUser($userId)
    {
        try {
            return static::raw('SELECT * FROM `@THIS` WHERE user = ?', [$userId]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $userId
     * @param $endpoint
     * @return bool
     * @throws \Exception
     */
    public static function isSubscribed($userId, $endpoint)
    {
        try {
            $row = static::raw('SELECT * FROM `@THIS` WHERE user = ? AND endpoint = ?', [$userId, $endpoint])->first();

            return $row !== null;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
