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
     * @param $weather_latitude
     * @param $weather_longitude
     * @return void
     * @throws \Exception
     */
    public static function editPlace($id, $name, $weather_latitude = null, $weather_longitude = null)
    {
        try {
            if ((!is_string($name)) || (strlen(trim($name)) === 0)) {
                throw new \Exception('A name is required');
            }

            static::raw('UPDATE `@THIS` SET name = ?, weather_latitude = ?, weather_longitude = ? WHERE id = ?', [
                trim($name), (is_numeric($weather_latitude) ? $weather_latitude : null), (is_numeric($weather_longitude) ? $weather_longitude : null), $id
            ]);
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

            static::clearPhoto($id);

            static::raw('DELETE FROM `@THIS` WHERE id = ?', [$id]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Uploads and stores a photo for a Place, mirroring
     * LocationsModel::setPhoto() exactly - same validation, storage
     * directory, thumbnailing and optimization pipeline, so a Place's
     * photo behaves identically to a Location's.
     *
     * @param $id
     * @return void
     * @throws \Exception
     */
    public static function setPhoto($id)
    {
        try {
            if ((!isset($_FILES['photo'])) || ($_FILES['photo']['error'] !== UPLOAD_ERR_OK)) {
                throw new \Exception('No image provided');
            }

            static::clearPhoto($id);

            $file_ext = UtilsModule::getImageExt($_FILES['photo']['tmp_name']);

            if ($file_ext === null) {
                throw new \Exception('File is not a valid image');
            }

            $file_name = md5(random_bytes(55) . date('Y-m-d H:i:s'));

            move_uploaded_file($_FILES['photo']['tmp_name'], public_path('/img/' . $file_name . '.' . $file_ext));

            $img_type = UtilsModule::getImageType($file_ext, public_path('/img/' . $file_name));

            UtilsModule::optimizeImage(public_path('/img/' . $file_name . '.' . $file_ext), $img_type);

            if (!UtilsModule::createThumbFile(public_path('/img/' . $file_name . '.' . $file_ext), $img_type, public_path('/img/' . $file_name), $file_ext)) {
                throw new \Exception('createThumbFile failed');
            }

            $fullFileName = $file_name . '_thumb.' . $file_ext;

            static::raw('UPDATE `@THIS` SET icon = ? WHERE id = ?', [$fullFileName, $id]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return void
     * @throws \Exception
     */
    public static function clearPhoto($id)
    {
        try {
            $item = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
            if (!$item) {
                throw new \Exception('Item not found: ' . $id);
            }

            if ($item->get('icon')) {
                $thumb_photo = $item->get('icon');
                $full_photo = str_replace('_thumb', '', $item->get('icon'));

                if (file_exists(public_path() . '/img/' . $thumb_photo)) {
                    unlink(public_path() . '/img/' . $thumb_photo);
                }

                if (file_exists(public_path() . '/img/' . $full_photo)) {
                    unlink(public_path() . '/img/' . $full_photo);
                }

                static::raw('UPDATE `@THIS` SET icon = NULL WHERE id = ?', [$id]);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
