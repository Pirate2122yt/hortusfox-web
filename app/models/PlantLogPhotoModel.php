<?php

/**
 * Class PlantLogPhotoModel
 *
 * Manages the (optional, multiple) photos attached to a single
 * plant journal entry (PlantLogModel).
 */
class PlantLogPhotoModel extends \Asatru\Database\Model {
    /**
     * @param $logEntry
     * @param $thumb
     * @param $original
     * @return void
     * @throws \Exception
     */
    public static function addPhoto($logEntry, $thumb, $original)
    {
        try {
            static::raw('INSERT INTO `@THIS` (log_entry, thumb, original) VALUES(?, ?, ?)', [
                $logEntry, $thumb, $original
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
     * @param $id
     * @return void
     * @throws \Exception
     */
    public static function removePhoto($id)
    {
        try {
            $item = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
            if (!$item) {
                return;
            }

            if ((strlen($item->get('thumb')) > 0) && (file_exists(public_path('/img/' . $item->get('thumb'))))) {
                unlink(public_path('/img/' . $item->get('thumb')));
            }

            if ((strlen($item->get('original')) > 0) && (file_exists(public_path('/img/' . $item->get('original'))))) {
                unlink(public_path('/img/' . $item->get('original')));
            }

            static::raw('DELETE FROM `@THIS` WHERE id = ?', [$id]);
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
            $rows = static::raw('SELECT * FROM `@THIS` WHERE log_entry = ?', [$logEntry]);
            foreach ($rows as $row) {
                static::removePhoto($row->get('id'));
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
