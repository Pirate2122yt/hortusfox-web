<?php

/**
 * Class PublicIdentifyRequestModel
 *
 * Tracks how many times each visitor (identified by a hash of their IP
 * address, never the raw address) has used the public plant identifier
 * today, to keep the shared PlantNet quota from being drained by a
 * single visitor or a script. See PublicController::identify_plant.
 */
class PublicIdentifyRequestModel extends \Asatru\Database\Model {
    /**
     * Requests a single visitor may make per calendar day.
     */
    const DAILY_LIMIT = 10;

    /**
     * How long old rows are kept around for, in days, before opportunistic
     * cleanup removes them. Only "today" is ever checked, so this is just
     * housekeeping to keep the table from growing forever.
     */
    const RETENTION_DAYS = 7;

    /**
     * @param $ip
     * @return string
     */
    private static function hashIp($ip)
    {
        return hash('sha256', (string)$ip);
    }

    /**
     * Atomically checks whether the given IP still has requests left today
     * and, if so, consumes one. Returns false (and consumes nothing) once
     * the daily limit has been reached.
     *
     * @param $ip
     * @return bool
     * @throws \Exception
     */
    public static function tryConsume($ip)
    {
        try {
            static::raw('DELETE FROM `@THIS` WHERE request_date < ?', [date('Y-m-d', strtotime('-' . self::RETENTION_DAYS . ' days'))]);

            $hash = static::hashIp($ip);
            $today = date('Y-m-d');

            $row = static::raw('SELECT * FROM `@THIS` WHERE ip_hash = ? AND request_date = ?', [$hash, $today])->first();

            if (!$row) {
                static::raw('INSERT INTO `@THIS` (ip_hash, request_date, request_count) VALUES (?, ?, 1)', [$hash, $today]);

                return true;
            }

            if ($row->get('request_count') >= self::DAILY_LIMIT) {
                return false;
            }

            static::raw('UPDATE `@THIS` SET request_count = request_count + 1 WHERE id = ?', [$row->get('id')]);

            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * How many identification requests the given IP has left today,
     * without consuming one. Used only to show a friendly count to the
     * visitor.
     *
     * @param $ip
     * @return int
     * @throws \Exception
     */
    public static function getRemaining($ip)
    {
        try {
            $hash = static::hashIp($ip);
            $today = date('Y-m-d');

            $row = static::raw('SELECT * FROM `@THIS` WHERE ip_hash = ? AND request_date = ?', [$hash, $today])->first();

            if (!$row) {
                return self::DAILY_LIMIT;
            }

            return max(0, self::DAILY_LIMIT - (int)$row->get('request_count'));
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
