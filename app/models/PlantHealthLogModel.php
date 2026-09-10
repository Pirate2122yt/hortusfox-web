<?php

/**
 * Class PlantHealthLogModel
 *
 * Records every health_state change a plant goes through over time, so
 * a simple "when was this plant unwell, and for how long" timeline can
 * be shown on its details page. A row is added whenever a plant is
 * created (seeding the starting state) and whenever its health_state
 * is actually changed to something new (see PlantsModel::editPlantAttribute).
 */
class PlantHealthLogModel extends \Asatru\Database\Model {
    /**
     * @param $plantId
     * @param $healthState
     * @return void
     * @throws \Exception
     */
    public static function addEntry($plantId, $healthState)
    {
        try {
            static::raw('INSERT INTO `@THIS` (plant, health_state) VALUES(?, ?)', [$plantId, $healthState]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Builds a chronological list of health-state "segments" for a
     * plant: each entry's recorded date paired with the date the next
     * entry took over (or now, for the current/last one), plus how
     * many days that segment lasted. Used to render a simple two-tone
     * (good vs. problem) status timeline rather than a misleading
     * chart across non-ordinal health states.
     *
     * @param $plantId
     * @return array a list of ['health_state', 'is_good', 'from', 'till', 'from_label', 'till_label', 'days']
     * @throws \Exception
     */
    public static function getSegmentsForPlant($plantId)
    {
        try {
            $entries = [];
            foreach (static::raw('SELECT * FROM `@THIS` WHERE plant = ? ORDER BY recorded_at ASC', [$plantId]) as $entry) {
                $entries[] = $entry;
            }

            $segments = [];
            $count = count($entries);

            for ($i = 0; $i < $count; $i++) {
                $from = $entries[$i]->get('recorded_at');
                $till = ($i + 1 < $count) ? $entries[$i + 1]->get('recorded_at') : date('Y-m-d H:i:s');

                $days = max(0.02, (strtotime($till) - strtotime($from)) / 86400);

                $segments[] = [
                    'health_state' => $entries[$i]->get('health_state'),
                    'is_good' => ($entries[$i]->get('health_state') === PlantsModel::PLANT_STATE_GOOD),
                    'from' => $from,
                    'till' => $till,
                    'from_label' => date('Y-m-d', strtotime($from)),
                    'till_label' => date('Y-m-d', strtotime($till)),
                    'days' => $days
                ];
            }

            return $segments;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
