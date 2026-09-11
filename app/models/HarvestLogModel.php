<?php

/**
 * Class HarvestLogModel
 *
 * A real harvest log per plant - date, quantity/unit, and notes -
 * separate from the calendar's "Harvest" event category, which has no
 * logging behind it (it's just a label for a reminder). Rows here are
 * added by hand from the plant details page.
 */
class HarvestLogModel extends \Asatru\Database\Model {
    /**
     * @param $plantId
     * @param $harvestDate
     * @param $quantity
     * @param $unit
     * @param $notes
     * @return void
     * @throws \Exception
     */
    public static function addEntry($plantId, $harvestDate, $quantity, $unit, $notes)
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            $plant = PlantsModel::getDetails($plantId);
            if (!$plant) {
                throw new \Exception('Plant not found: ' . $plantId);
            }

            if (!$plant->get('harvest_tracking_enabled')) {
                throw new \Exception('Harvest tracking is not enabled for this plant');
            }

            static::raw('INSERT INTO `@THIS` (plant, harvest_date, quantity, unit, notes) VALUES(?, ?, ?, ?, ?)', [
                $plantId,
                $harvestDate,
                (strlen(trim((string)$quantity)) > 0) ? $quantity : null,
                (strlen(trim((string)$unit)) > 0) ? trim($unit) : null,
                $notes
            ]);

            LogModel::addLog($user->get('id'), $plantId, 'harvest_logged', $harvestDate, url('/plants/details/' . $plantId));
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $plantId
     * @return mixed
     * @throws \Exception
     */
    public static function getForPlant($plantId)
    {
        try {
            return static::raw('SELECT * FROM `@THIS` WHERE plant = ? ORDER BY harvest_date DESC, id DESC', [$plantId]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Every distinct unit a plant has been harvested in, each with its
     * running total - kept separate rather than summed together since
     * "4 g + 2 pieces" isn't a meaningful single number.
     *
     * @param $plantId
     * @return array a list of ['unit' => string|null, 'total' => float]
     * @throws \Exception
     */
    public static function getTotalsForPlant($plantId)
    {
        try {
            $totals = [];
            foreach (static::raw('SELECT unit, SUM(quantity) AS total FROM `@THIS` WHERE plant = ? AND quantity IS NOT NULL GROUP BY unit', [$plantId]) as $row) {
                $totals[] = [
                    'unit' => $row->get('unit'),
                    'total' => (float)$row->get('total')
                ];
            }

            return $totals;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return void
     * @throws \Exception
     */
    public static function removeEntry($id)
    {
        try {
            $entry = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
            if (!$entry) {
                throw new \Exception('Harvest entry not found: ' . $id);
            }

            static::raw('DELETE FROM `@THIS` WHERE id = ?', [$id]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Every plant this row's harvest entry belongs to, for the remove
     * action's redirect - looked up before the row is deleted.
     *
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public static function getPlantIdForEntry($id)
    {
        try {
            $entry = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
            return ($entry) ? $entry->get('plant') : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
