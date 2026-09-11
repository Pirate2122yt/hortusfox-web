<?php

/**
 * Class WishlistModel
 *
 * Plant wishlist entries: things a user wants to get but doesn't own
 * yet. Each entry belongs to the user who added it. Everyone signed in
 * can browse everyone else's wishlist (the "Overall" and "Per-Place"
 * views) for gift-giving/coordination purposes, but only the owner (or
 * an admin) may edit, remove, or upload a photo for an entry - the same
 * ownership idiom as FeatureRequestModel.
 */
class WishlistModel extends \Asatru\Database\Model {
    const PRIORITY_MUST_HAVE = 'must_have';
    const PRIORITY_WOULD_LIKE = 'would_like';
    const PRIORITY_SOMEDAY = 'someday';

    static $priorities = [
        self::PRIORITY_MUST_HAVE,
        self::PRIORITY_WOULD_LIKE,
        self::PRIORITY_SOMEDAY
    ];

    /**
     * @param $priority
     * @return void
     * @throws \Exception
     */
    public static function validatePriority($priority)
    {
        if (!in_array($priority, static::$priorities)) {
            throw new \Exception('Invalid priority: ' . $priority);
        }
    }

    /**
     * A plain Bulma tag color for a priority, so the badge needs no new
     * CSS of its own (Bulma is already loaded site-wide).
     *
     * @param $priority
     * @return string
     */
    public static function getPriorityTagClass($priority)
    {
        switch ($priority) {
            case self::PRIORITY_MUST_HAVE:
                return 'is-danger';
            case self::PRIORITY_WOULD_LIKE:
                return 'is-warning';
            default:
                return 'is-info is-light';
        }
    }

    /**
     * @param $id
     * @param $user
     * @return mixed
     * @throws \Exception
     */
    private static function requireOwned($id, $user)
    {
        $item = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
        if (!$item) {
            throw new \Exception('Invalid wishlist item: ' . $id);
        }

        if (($item->get('user') != $user->get('id')) && (!$user->get('admin'))) {
            throw new \Exception('Not allowed to modify this wishlist item');
        }

        return $item;
    }

    /**
     * @param $name
     * @param $species
     * @param $cultivar
     * @param $notes
     * @param $priority
     * @param $location
     * @param $source_url
     * @param $price
     * @param $best_time_note
     * @return int
     * @throws \Exception
     */
    public static function addItem($name, $species, $cultivar, $notes, $priority, $location, $source_url, $price, $best_time_note)
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            if ((!is_string($name)) || (strlen(trim($name)) === 0)) {
                throw new \Exception('A name is required');
            }

            static::validatePriority($priority);

            static::raw('INSERT INTO `@THIS` (user, name, species, cultivar, notes, priority, location, source_url, price, best_time_note) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                $user->get('id'), trim($name), $species ?: null, $cultivar ?: null, $notes ?: null, $priority,
                ($location ?: null), $source_url ?: null, static::normalizePrice($price), $best_time_note ?: null
            ]);

            $item = static::raw('SELECT * FROM `@THIS` ORDER BY id DESC LIMIT 1')->first();

            return ($item) ? $item->get('id') : 0;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @param $name
     * @param $species
     * @param $cultivar
     * @param $notes
     * @param $priority
     * @param $location
     * @param $source_url
     * @param $price
     * @param $best_time_note
     * @return void
     * @throws \Exception
     */
    public static function editItem($id, $name, $species, $cultivar, $notes, $priority, $location, $source_url, $price, $best_time_note)
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            static::requireOwned($id, $user);

            if ((!is_string($name)) || (strlen(trim($name)) === 0)) {
                throw new \Exception('A name is required');
            }

            static::validatePriority($priority);

            static::raw('UPDATE `@THIS` SET name = ?, species = ?, cultivar = ?, notes = ?, priority = ?, location = ?, source_url = ?, price = ?, best_time_note = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [
                trim($name), $species ?: null, $cultivar ?: null, $notes ?: null, $priority,
                ($location ?: null), $source_url ?: null, static::normalizePrice($price), $best_time_note ?: null, $id
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return void
     * @throws \Exception
     */
    public static function removeItem($id)
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            $item = static::requireOwned($id, $user);

            if (($item->get('photo')) && (file_exists(public_path('/img/' . $item->get('photo'))))) {
                unlink(public_path('/img/' . $item->get('photo')));
            }

            static::raw('DELETE FROM `@THIS` WHERE id = ?', [$id]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Uploads (or replaces) a wishlist item's photo, following the same
     * upload/optimize/thumbnail pipeline as PlantsModel::editPlantPhoto -
     * only the thumbnail is kept, same as PlantsModel.photo.
     *
     * @param $id
     * @return void
     * @throws \Exception
     */
    public static function uploadPhoto($id)
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            static::requireOwned($id, $user);

            if ((!isset($_FILES['photo'])) || ($_FILES['photo']['error'] !== UPLOAD_ERR_OK)) {
                throw new \Exception('Errorneous file');
            }

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

            static::raw('UPDATE `@THIS` SET photo = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [$file_name . '_thumb.' . $file_ext, $id]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public static function getDetails($id)
    {
        try {
            return static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * "My Wishlist" - just the given user's own entries.
     *
     * @param $userId
     * @return mixed
     * @throws \Exception
     */
    public static function getForUser($userId)
    {
        try {
            return static::raw('SELECT * FROM `@THIS` WHERE user = ? ORDER BY FIELD(priority, \'must_have\', \'would_like\', \'someday\'), created_at DESC', [$userId]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * "Overall" - every user's wishlist entries combined.
     *
     * @return mixed
     * @throws \Exception
     */
    public static function getAll()
    {
        try {
            return static::raw('SELECT * FROM `@THIS` ORDER BY FIELD(priority, \'must_have\', \'would_like\', \'someday\'), created_at DESC');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * "House Wishlist" - every entry whose Location falls within the
     * given set of Location ids (i.e. all the Locations belonging to one
     * chosen Place). The caller resolves the Place's Location ids first
     * (via LocationsModel::getByPlace) - a Place is a derived grouping,
     * not something queried directly here, same as the homepage's
     * places_overview.
     *
     * @param $locationIds
     * @return mixed
     * @throws \Exception
     */
    public static function getByLocationIds($locationIds)
    {
        try {
            if ((!is_array($locationIds)) || (count($locationIds) === 0)) {
                return [];
            }

            $placeholders = implode(',', array_fill(0, count($locationIds), '?'));

            return static::raw('SELECT * FROM `@THIS` WHERE location IN (' . $placeholders . ') ORDER BY FIELD(priority, \'must_have\', \'would_like\', \'someday\'), created_at DESC', $locationIds);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $items
     * @return float
     */
    public static function getTotalPrice($items)
    {
        $total = 0.0;

        if (is_countable($items)) {
            foreach ($items as $item) {
                if ($item->get('price') !== null) {
                    $total += (float)$item->get('price');
                }
            }
        }

        return $total;
    }

    /**
     * The DB column is DECIMAL(12, 2) - up to 10 digits before the
     * decimal point. MySQL rejects an out-of-range insert outright
     * (SQLSTATE 22003) instead of truncating it, which surfaces as a
     * raw SQL error rather than a useful one - so this is checked here
     * first, against the same bound, to fail with a message that
     * actually says what's wrong.
     */
    const MAX_PRICE = 9999999999.99;

    /**
     * Empty string/non-numeric input becomes NULL rather than 0, so an
     * item without a known price doesn't silently show up as "$0.00".
     *
     * @param $price
     * @return float|null
     * @throws \Exception
     */
    private static function normalizePrice($price)
    {
        if ((!is_numeric($price)) || ((string)$price === '')) {
            return null;
        }

        $normalized = round((float)$price, 2);

        if (($normalized < 0) || ($normalized > self::MAX_PRICE)) {
            throw new \Exception('Price must be between 0 and ' . number_format(self::MAX_PRICE, 2));
        }

        return $normalized;
    }
}
