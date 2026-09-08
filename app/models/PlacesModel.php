<?php

/**
 * Class PlacesModel
 *
 * A Place groups Locations together (e.g. a house, containing rooms as
 * Locations). Plants and Inventory keep referencing a Location as
 * before; Place is purely an organizational layer above it.
 */
class PlacesModel extends \Asatru\Database\Model {
    /**
     * @return mixed
     * @throws \Exception
     */
    public static function getAll()
    {
        try {
            return static::raw('SELECT * FROM `@THIS` ORDER BY name ASC');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public static function getById($id)
    {
        try {
            return static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return string|null
     * @throws \Exception
     */
    public static function getNameById($id)
    {
        try {
            return static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first()?->get('name');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return int
     * @throws \Exception
     */
    public static function getCount()
    {
        try {
            return static::raw('SELECT COUNT(*) as count FROM `@THIS`')->first()->get('count');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $name
     * @return void
     * @throws \Exception
     */
    public static function addPlace($name)
    {
        try {
            if ((!is_string($name)) || (strlen(trim($name)) === 0)) {
                throw new \Exception('A name is required');
            }

            static::raw('INSERT INTO `@THIS` (name) VALUES(?)', [trim($name)]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @param $name
     * @return void
     * @throws \Exception
     */
    public static function editPlace($id, $name)
    {
        try {
            if ((!is_string($name)) || (strlen(trim($name)) === 0)) {
                throw new \Exception('A name is required');
            }

            static::raw('UPDATE `@THIS` SET name = ? WHERE id = ?', [trim($name), $id]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Removes a Place. If it still has Locations in it, they are moved
     * to $target first (a Place cannot be removed while it's the only
     * one left and still has Locations, same rule as removing the last
     * Location while it still has plants).
     *
     * @param $id
     * @param $target
     * @return void
     * @throws \Exception
     */
    public static function removePlace($id, $target)
    {
        try {
            if ((static::getCount() <= 1) && (LocationsModel::getCountForPlace($id) > 0)) {
                throw new \Exception(__('app.error_place_not_empty'));
            }

            LocationsModel::migrateLocationsToPlace($id, $target);

            static::raw('DELETE FROM `@THIS` WHERE id = ?', [$id]);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
