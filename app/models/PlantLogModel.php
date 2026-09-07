<?php

/**
 * Class PlantLogModel
 * 
 * Management of plant specific journal/log entries made by users.
 * Each entry may optionally carry an uploaded photo and a set of
 * space-separated tags, similar to the plant-level tags field.
 */ 
class PlantLogModel extends \Asatru\Database\Model {
    /**
     * Handles an optional 'photo' file upload for a journal entry.
     * Mirrors PlantPhotoModel::uploadPhoto but the photo is optional.
     *
     * @return array [thumb, original] filenames, both null if no photo was uploaded
     * @throws \Exception
     */
    private static function handleOptionalPhotoUpload()
    {
        if ((!isset($_FILES['photo'])) || (!isset($_FILES['photo']['tmp_name'])) || (strlen($_FILES['photo']['tmp_name']) === 0) || ($_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE)) {
            return [null, null];
        }

        if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('Errorneous file');
        }

        $file_ext = UtilsModule::getImageExt($_FILES['photo']['tmp_name']);

        if ($file_ext === null) {
            throw new \Exception('File is not a valid image');
        }

        $file_name = md5(random_bytes(55) . date('Y-m-d H:i:s'));

        move_uploaded_file($_FILES['photo']['tmp_name'], public_path('/img/' . $file_name . '.' . $file_ext));

        if (!UtilsModule::createThumbFile(public_path('/img/' . $file_name . '.' . $file_ext), UtilsModule::getImageType($file_ext, public_path('/img/' . $file_name)), public_path('/img/' . $file_name), $file_ext)) {
            throw new \Exception('createThumbFile failed');
        }

        return [$file_name . '_thumb.' . $file_ext, $file_name . '.' . $file_ext];
    }

    /**
     * @param $thumb
     * @param $original
     * @return void
     */
    private static function deletePhotoFiles($thumb, $original)
    {
        if ((is_string($thumb)) && (strlen($thumb) > 0) && (file_exists(public_path('/img/' . $thumb)))) {
            unlink(public_path('/img/' . $thumb));
        }

        if ((is_string($original)) && (strlen($original) > 0) && (file_exists(public_path('/img/' . $original)))) {
            unlink(public_path('/img/' . $original));
        }
    }

    /**
     * @param $tags
     * @return string
     */
    private static function normalizeTags($tags)
    {
        if (!is_string($tags)) {
            return '';
        }

        $parts = preg_split('/\s+/', trim($tags));
        $parts = array_filter($parts, function($t) {
            return strlen($t) > 0;
        });

        return implode(' ', $parts);
    }

    /**
     * @param $plant
     * @param $content
     * @param $tags
     * @param $api
     * @return int
     * @throws \Exception
     */
    public static function addEntry($plant, $content, $tags = '', $api = false)
    {
        try {
            $user = null;

            if (!$api) {
                $user = UserModel::getAuthUser();
                if (!$user) {
                    throw new \Exception('Invalid user');
                }
            }

            $tags = static::normalizeTags($tags);
            [$photo_thumb, $photo_original] = static::handleOptionalPhotoUpload();

            static::raw('INSERT INTO `@THIS` (plant, content, tags, photo_thumb, photo_original) VALUES(?, ?, ?, ?, ?)', [
                $plant, $content, $tags, $photo_thumb, $photo_original
            ]);

            if (!$api) {
                LogModel::addLog($user->get('id'), $plant, 'add_plant_log', $content, url('/plants/details/' . $plant));
            }

            $item = static::raw('SELECT * FROM `@THIS` ORDER BY id DESC LIMIT 1')->first();
            if ($item) {
                return $item->get('id');
            }

            return 0;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @param $content
     * @param $tags
     * @param $removePhoto
     * @param $api
     * @return void
     * @throws \Exception
     */
    public static function editEntry($id, $content, $tags = '', $removePhoto = false, $api = false)
    {
        try {
            $user = null;

            if (!$api) {
                $user = UserModel::getAuthUser();
                if (!$user) {
                    throw new \Exception('Invalid user');
                }
            }

            $item = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
            if (!$item) {
                throw new \Exception('Invalid entry: ' . $id);
            }

            $tags = static::normalizeTags($tags);

            [$new_thumb, $new_original] = static::handleOptionalPhotoUpload();

            $photo_thumb = $item->get('photo_thumb');
            $photo_original = $item->get('photo_original');

            if ($new_thumb !== null) {
                static::deletePhotoFiles($photo_thumb, $photo_original);
                $photo_thumb = $new_thumb;
                $photo_original = $new_original;
            } else if ($removePhoto) {
                static::deletePhotoFiles($photo_thumb, $photo_original);
                $photo_thumb = null;
                $photo_original = null;
            }

            static::raw('UPDATE `@THIS` SET content = ?, tags = ?, photo_thumb = ?, photo_original = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [
                $content, $tags, $photo_thumb, $photo_original, $item->get('id')
            ]);

            if (!$api) {
                LogModel::addLog($user->get('id'), $item->get('id'), 'edit_plant_log', $content, url('/plants/details/' . $item->get('plant')));
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @param $api
     * @return void
     * @throws \Exception
     */
    public static function removeEntry($id, $api = false)
    {
        try {
            $user = null;

            if (!$api) {
                $user = UserModel::getAuthUser();
                if (!$user) {
                    throw new \Exception('Invalid user');
                }
            }

            $item = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
            if (!$item) {
                throw new \Exception('Invalid entry: ' . $id);
            }

            static::deletePhotoFiles($item->get('photo_thumb'), $item->get('photo_original'));

            static::raw('DELETE FROM `@THIS` WHERE id = ?', [
                $item->get('id')
            ]);

            if (!$api) {
                LogModel::addLog($user->get('id'), $item->get('id'), 'remove_plant_log', '', url('/plants/details/' . $item->get('plant')));
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $plant
     * @param $paginate
     * @param $limit
     * @return mixed
     * @throws \Exception
     */
    public static function getLogEntries($plant, $paginate = null, $limit = 10)
    {
        try {
            if ($paginate) {
                return static::raw('SELECT * FROM `@THIS` WHERE plant = ? AND id < ? ORDER BY id DESC LIMIT ' . $limit, [$plant, $paginate]);
            } else {
                return static::raw('SELECT * FROM `@THIS` WHERE plant = ? ORDER BY id DESC LIMIT ' . $limit, [$plant]);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
