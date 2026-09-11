<?php

/**
 * Class ChangelogModel
 *
 * A short admin-curated history of notable changes to the app,
 * surfaced on its own page linked from the Feature Request board.
 * Unlike a "Feature request marked as Added", an entry here has no
 * requester and no votes - it's simply a dated note an admin adds
 * once a change has actually shipped.
 */
class ChangelogModel extends \Asatru\Database\Model {
    /**
     * @param $title
     * @param $description
     * @param $entryDate
     * @return int
     * @throws \Exception
     */
    public static function addEntry($title, $description, $entryDate = null)
    {
        try {
            static::requireAdmin();

            if ((!is_string($title)) || (strlen(trim($title)) === 0)) {
                throw new \Exception('A title is required');
            }

            static::raw('INSERT INTO `@THIS` (title, description, entry_date) VALUES(?, ?, ?)', [
                trim($title), trim($description ?? ''), static::normalizeDate($entryDate)
            ]);

            $item = static::raw('SELECT * FROM `@THIS` ORDER BY id DESC LIMIT 1')->first();

            return ($item) ? $item->get('id') : 0;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @param $title
     * @param $description
     * @param $entryDate
     * @return void
     * @throws \Exception
     */
    public static function editEntry($id, $title, $description, $entryDate = null)
    {
        try {
            static::requireAdmin();

            if ((!is_string($title)) || (strlen(trim($title)) === 0)) {
                throw new \Exception('A title is required');
            }

            $item = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
            if (!$item) {
                throw new \Exception('Invalid changelog entry: ' . $id);
            }

            static::raw('UPDATE `@THIS` SET title = ?, description = ?, entry_date = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [
                trim($title), trim($description ?? ''), static::normalizeDate($entryDate), $item->get('id')
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
    public static function removeEntry($id)
    {
        try {
            static::requireAdmin();

            $item = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
            if (!$item) {
                throw new \Exception('Invalid changelog entry: ' . $id);
            }

            static::raw('DELETE FROM `@THIS` WHERE id = ?', [$item->get('id')]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Every entry, newest first.
     *
     * @return mixed
     * @throws \Exception
     */
    public static function getAll()
    {
        try {
            return static::raw('SELECT * FROM `@THIS` ORDER BY entry_date DESC, id DESC');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Only an admin may add, edit, or remove a changelog entry - unlike
     * a feature request, there's no owning user to fall back to.
     *
     * @return void
     * @throws \Exception
     */
    private static function requireAdmin()
    {
        $user = UserModel::getAuthUser();
        if ((!$user) || (!$user->get('admin'))) {
            throw new \Exception('Not allowed to manage the changelog');
        }
    }

    /**
     * Empty/missing input defaults to today; anything else must parse
     * as a real date.
     *
     * @param $entryDate
     * @return string
     * @throws \Exception
     */
    private static function normalizeDate($entryDate)
    {
        if ((!is_string($entryDate)) || (strlen(trim($entryDate)) === 0)) {
            return date('Y-m-d');
        }

        $timestamp = strtotime($entryDate);
        if ($timestamp === false) {
            throw new \Exception('Invalid date: ' . $entryDate);
        }

        return date('Y-m-d', $timestamp);
    }
}
