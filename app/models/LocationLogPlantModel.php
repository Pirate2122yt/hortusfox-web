<?php

/**
 * Class LocationLogPlantModel
 *
 * Join table recording which plants (at a location) a given location
 * journal entry (LocationLogModel) applies to.
 */
class LocationLogPlantModel extends \Asatru\Database\Model {
    /**
     * @param $logEntry
     * @param $plant
     * @return void
     * @throws \Exception
     */
    public static function addPlant($logEntry, $plant)
    {
        try {
            static::raw('INSERT INTO `@THIS` (log_entry, plant) VALUES(?, ?)', [
                $logEntry, $plant
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $logEntry
     * @return mixed
     * @throws \Exception
     */
    public static function getForEntry($logEntry)
    {
        try {
            return static::raw('SELECT * FROM `@THIS` WHERE log_entry = ? ORDER BY id ASC', [$logEntry]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $logEntry
     * @return array list of plant ids
     * @throws \Exception
     */
    public static function getPlantIdsForEntry($logEntry)
    {
        try {
            $ids = [];
            $rows = static::raw('SELECT * FROM `@THIS` WHERE log_entry = ? ORDER BY id ASC', [$logEntry]);
            foreach ($rows as $row) {
                $ids[] = $row->get('plant');
            }

            return $ids;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $logEntry
     * @return void
     * @throws \Exception
     */
    public static function clearForEntry($logEntry)
    {
        try {
            static::raw('DELETE FROM `@THIS` WHERE log_entry = ?', [$logEntry]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Replaces the full set of plants a location journal entry applies
     * to with the given list of plant ids.
     *
     * @param $logEntry
     * @param $plantIds
     * @return void
     * @throws \Exception
     */
    public static function setForEntry($logEntry, $plantIds)
    {
        try {
            static::clearForEntry($logEntry);

            if (!is_array($plantIds)) {
                return;
            }

            foreach ($plantIds as $plantId) {
                if ((is_numeric($plantId)) && ((int)$plantId > 0)) {
                    static::addPlant($logEntry, (int)$plantId);
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
