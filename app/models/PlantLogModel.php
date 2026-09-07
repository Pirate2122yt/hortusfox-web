<?php

/**
 * Class PlantLogModel
 * 
 * Management of plant specific journal/log entries made by users.
 * Each entry has a title and an optional multi-line body, may carry
 * any number of uploaded photos (see PlantLogPhotoModel) and a set
 * of space-separated tags, and is flagged as either a user-authored
 * entry or an automatic system entry (is_system).
 */ 
class PlantLogModel extends \Asatru\Database\Model {
    /**
     * Handles any number of uploaded 'photos[]' files for a journal entry.
     *
     * @return array list of [thumb, original] filename pairs
     * @throws \Exception
     */
    private static function handlePhotoUploads()
    {
        $results = [];

        if ((!isset($_FILES['photos'])) || (!isset($_FILES['photos']['tmp_name'])) || (!is_array($_FILES['photos']['tmp_name']))) {
            return $results;
        }

        $count = count($_FILES['photos']['tmp_name']);

        for ($i = 0; $i < $count; $i++) {
            if ((!isset($_FILES['photos']['tmp_name'][$i])) || (strlen($_FILES['photos']['tmp_name'][$i]) === 0) || ($_FILES['photos']['error'][$i] === UPLOAD_ERR_NO_FILE)) {
                continue;
            }

            if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) {
                throw new \Exception('Errorneous file');
            }

            $file_ext = UtilsModule::getImageExt($_FILES['photos']['tmp_name'][$i]);

            if ($file_ext === null) {
                throw new \Exception('File is not a valid image');
            }

            $file_name = md5(random_bytes(55) . date('Y-m-d H:i:s') . $i);

            move_uploaded_file($_FILES['photos']['tmp_name'][$i], public_path('/img/' . $file_name . '.' . $file_ext));

            if (!UtilsModule::createThumbFile(public_path('/img/' . $file_name . '.' . $file_ext), UtilsModule::getImageType($file_ext, public_path('/img/' . $file_name)), public_path('/img/' . $file_name), $file_ext)) {
                throw new \Exception('createThumbFile failed');
            }

            $results[] = [$file_name . '_thumb.' . $file_ext, $file_name . '.' . $file_ext];
        }

        return $results;
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
     * Normalizes a user-supplied entry date (expected as Y-m-d), falling
     * back to today's date if empty or not a valid date.
     *
     * @param $entryDate
     * @return string
     */
    private static function normalizeEntryDate($entryDate)
    {
        if (is_string($entryDate) && (strlen(trim($entryDate)) > 0)) {
            $parsed = \DateTime::createFromFormat('Y-m-d', trim($entryDate));
            if (($parsed !== false) && ($parsed->format('Y-m-d') === trim($entryDate))) {
                return $parsed->format('Y-m-d');
            }
        }

        return date('Y-m-d');
    }

    /**
     * @param $plant
     * @param $title
     * @param $content
     * @param $tags
     * @param $api
     * @param $isSystem
     * @param $entryDate
     * @return int
     * @throws \Exception
     */
    public static function addEntry($plant, $title, $content = '', $tags = '', $api = false, $isSystem = false, $entryDate = null)
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
            $entryDate = static::normalizeEntryDate($entryDate);

            static::raw('INSERT INTO `@THIS` (plant, title, content, tags, is_system, entry_date) VALUES(?, ?, ?, ?, ?, ?)', [
                $plant, $title, $content, $tags, ($isSystem ? 1 : 0), $entryDate
            ]);

            $item = static::raw('SELECT * FROM `@THIS` ORDER BY id DESC LIMIT 1')->first();
            $entryId = ($item) ? $item->get('id') : 0;

            if ($entryId > 0) {
                foreach (static::handlePhotoUploads() as $photo) {
                    PlantLogPhotoModel::addPhoto($entryId, $photo[0], $photo[1]);
                }
            }

            if (!$api) {
                LogModel::addLog($user->get('id'), $plant, 'add_plant_log', $title, url('/plants/details/' . $plant));
            }

            return $entryId;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @param $title
     * @param $content
     * @param $tags
     * @param $removePhotoIds
     * @param $api
     * @param $entryDate
     * @return void
     * @throws \Exception
     */
    public static function editEntry($id, $title, $content = '', $tags = '', $removePhotoIds = [], $api = false, $entryDate = null)
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
            $entryDate = static::normalizeEntryDate($entryDate ?? $item->get('entry_date'));

            static::raw('UPDATE `@THIS` SET title = ?, content = ?, tags = ?, entry_date = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [
                $title, $content, $tags, $entryDate, $item->get('id')
            ]);

            if (is_array($removePhotoIds) && count($removePhotoIds) > 0) {
                $existingPhotos = PlantLogPhotoModel::getForEntry($item->get('id'));
                $existingIds = [];
                foreach ($existingPhotos as $existingPhoto) {
                    $existingIds[] = (string)$existingPhoto->get('id');
                }

                foreach ($removePhotoIds as $photoId) {
                    if (in_array((string)$photoId, $existingIds)) {
                        PlantLogPhotoModel::removePhoto($photoId);
                    }
                }
            }

            foreach (static::handlePhotoUploads() as $photo) {
                PlantLogPhotoModel::addPhoto($item->get('id'), $photo[0], $photo[1]);
            }

            if (!$api) {
                LogModel::addLog($user->get('id'), $item->get('id'), 'edit_plant_log', $title, url('/plants/details/' . $item->get('plant')));
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

            PlantLogPhotoModel::clearForEntry($item->get('id'));

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
     * Entries are ordered chronologically by entry_date (the date the
     * event actually happened, which may be backdated), falling back to
     * id as a tiebreaker for same-day entries.
     *
     * @param $plant
     * @param $paginate id of the last entry on the previous page (tiebreaker)
     * @param $paginateDate entry_date of the last entry on the previous page
     * @param $limit
     * @return mixed
     * @throws \Exception
     */
    public static function getLogEntries($plant, $paginate = null, $paginateDate = null, $limit = 10)
    {
        try {
            if ($paginate && $paginateDate) {
                return static::raw('SELECT * FROM `@THIS` WHERE plant = ? AND (entry_date < ? OR (entry_date = ? AND id < ?)) ORDER BY entry_date DESC, id DESC LIMIT ' . $limit, [$plant, $paginateDate, $paginateDate, $paginate]);
            } else if ($paginate) {
                return static::raw('SELECT * FROM `@THIS` WHERE plant = ? AND id < ? ORDER BY entry_date DESC, id DESC LIMIT ' . $limit, [$plant, $paginate]);
            } else {
                return static::raw('SELECT * FROM `@THIS` WHERE plant = ? ORDER BY entry_date DESC, id DESC LIMIT ' . $limit, [$plant]);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
