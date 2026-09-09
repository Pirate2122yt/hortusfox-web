<?php

/**
 * Class LocationsModel
 * 
 * Manages plant locations
 */ 
class LocationsModel extends \Asatru\Database\Model {
    /**
     * @param $only_active
     * @return mixed
     * @throws \Exception
     */
    public static function getAll($only_active = true)
    {
        try {
            if ($only_active) {
                return static::raw('SELECT * FROM `@THIS` WHERE active = 1 ORDER BY name ASC');
            } else {
                return static::raw('SELECT * FROM `@THIS` ORDER BY name ASC');
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $only_active
     * @param $paginate
     * @param $limit
     */
    public static function getPaginated($only_active = true, $paginate = null, $limit = null)
    {
        try {
            $limit = ((is_numeric($limit) && ($limit > 0)) ? 'LIMIT ' . $limit : '');

            if ($paginate === null) {
                if ($only_active) {
                    return static::raw('SELECT * FROM `@THIS` WHERE active = 1 ORDER BY id ASC ' . $limit);
                } else {
                    return static::raw('SELECT * FROM `@THIS` ORDER BY id ASC ' . $limit);
                }
            } else {
                if ($only_active) {
                    return static::raw('SELECT * FROM `@THIS` WHERE active = 1 AND id >= ? ORDER BY id ASC ' . $limit, [$paginate]);
                } else {
                    return static::raw('SELECT * FROM `@THIS` WHERE id >= ? ORDER BY id ASC ' . $limit, [$paginate]);
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return string
     * @throws \Exception
     */
    public static function getNameById($id)
    {
        try {
            return static::raw('SELECT * FROM `@THIS` WHERE id = ? LIMIT 1', [$id])->first()?->get('name');
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
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public static function getLocationById($id)
    {
        try {
            return static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $name
     * @param $place
     * @return void
     * @throws \Exception
     */
    public static function addLocation($name, $place = null)
    {
        try {
            static::raw('INSERT INTO `@THIS` (name, place) VALUES(?, ?)', [
                $name, ($place ?: null)
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @param $name
     * @param $active
     * @param $place
     * @param $weather_latitude
     * @param $weather_longitude
     * @return void
     * @throws \Exception
     */
    public static function editLocation($id, $name, $active, $place = null, $weather_latitude = null, $weather_longitude = null)
    {
        try {
            static::raw('UPDATE `@THIS` SET name = ?, active = ?, place = ?, weather_latitude = ?, weather_longitude = ? WHERE id = ?', [
                $name, $active, ($place ?: null), (is_numeric($weather_latitude) ? $weather_latitude : null), (is_numeric($weather_longitude) ? $weather_longitude : null), $id
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $placeId
     * @param $only_active
     * @return mixed
     * @throws \Exception
     */
    public static function getByPlace($placeId, $only_active = true)
    {
        try {
            if ($only_active) {
                return static::raw('SELECT * FROM `@THIS` WHERE place = ? AND active = 1 ORDER BY name ASC', [$placeId]);
            } else {
                return static::raw('SELECT * FROM `@THIS` WHERE place = ? ORDER BY name ASC', [$placeId]);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Locations that have never been assigned a Place (e.g. from
     * before Places existed).
     *
     * @param $only_active
     * @return mixed
     * @throws \Exception
     */
    public static function getUnassignedToPlace($only_active = true)
    {
        try {
            if ($only_active) {
                return static::raw('SELECT * FROM `@THIS` WHERE place IS NULL AND active = 1 ORDER BY name ASC');
            } else {
                return static::raw('SELECT * FROM `@THIS` WHERE place IS NULL ORDER BY name ASC');
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $placeId
     * @return int
     * @throws \Exception
     */
    public static function getCountForPlace($placeId)
    {
        try {
            return (int)static::raw('SELECT COUNT(*) as count FROM `@THIS` WHERE place = ?', [$placeId])->first()->get('count');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Reassigns every Location in $fromPlace to $toPlace, used when a
     * Place is removed.
     *
     * @param $fromPlace
     * @param $toPlace
     * @return void
     * @throws \Exception
     */
    public static function migrateLocationsToPlace($fromPlace, $toPlace)
    {
        try {
            static::raw('UPDATE `@THIS` SET place = ? WHERE place = ?', [($toPlace ?: null), $fromPlace]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
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

    /**
     * @param $id
     * @param $notes
     * @return void
     * @throws \Exception
     */
    public static function saveNotes($id, $notes)
    {
        try {
            static::raw('UPDATE `@THIS` SET notes = ? WHERE id = ?', [
                $notes, $id
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @param $target
     * @return void
     * @throws \Exception
     */
    public static function removeLocation($id, $target)
    {
        try {
            if ((static::getCount() <= 1) && (PlantsModel::getPlantCount($id))) {
                throw new \Exception(__('app.error_room_not_empty'));
            }

            PlantsModel::migratePlants($id, $target);

            static::clearPhoto($id);

            static::raw('DELETE FROM `@THIS` WHERE id = ?', [$id]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return bool
     * @throws \Exception
     */
    public static function isActive($id)
    {
        try {
            $data = static::raw('SELECT * FROM `@THIS` WHERE id = ? AND active = 1', [$id])->first();
            return $data !== null;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}