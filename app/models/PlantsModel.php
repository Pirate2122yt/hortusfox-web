<?php

use chillerlan\QRCode\{QRCode, QROptions};

/**
 * Class PlantsModel
 * 
 * Extensive management of plants
 */ 
class PlantsModel extends \Asatru\Database\Model {
    const PLANT_STATE_GOOD = 'in_good_standing';
    const PLANT_LONG_TEXT_THRESHOLD = 22;
    const PLANT_PLACEHOLDER_FILE = 'placeholder.jpg';
    const PLANT_LAST_UPDATED_AUTHORED_COUNT = 8;
    const PLANT_LIST_MAX_STRLEN = 15;

    static $sorting_list = [
        'name',
        'last_edited_date',
        'last_watered',
        'last_repotted',
        'last_fertilised',
        'health_state',
        'hardy',
        'lifespan',
        'light_level',
        'humidity',
        'cutting_month',
        'date_of_purchase',
        'history_date'
    ];

    static $sorting_dir = [
        'asc',
        'desc'
    ];

    static $allowed_attributes = [
        'name',
        'scientific_name',
        'knowledge_link',
        'location',
        'tags',
        'photo',
        'last_watered',
        'last_repotted',
        'last_fertilised',
        'lifespan',
        'hardy',
        'cutting_month',
        'date_of_purchase',
        'humidity',
        'light_level',
        'health_state',
        'notes',
        'history',
        'history_date',
        'is_public',
        'water_interval_days',
        'fertilise_interval_days',
        'repot_interval_days',
        'parent_plant'
    ];

    static $care_actions = [
        'water' => [
            'interval' => 'water_interval_days',
            'last' => 'last_watered'
        ],

        'fertilise' => [
            'interval' => 'fertilise_interval_days',
            'last' => 'last_fertilised'
        ],

        'repot' => [
            'interval' => 'repot_interval_days',
            'last' => 'last_repotted'
        ]
    ];

    static $plant_health_states = [
        'in_good_standing' => [
            'localization' => 'app.in_good_standing',
            'icon' => null
        ],

        'overwatered' => [
            'localization' => 'app.overwatered',
            'icon' => 'fas fa-water'
        ],

        'withering' => [
            'localization' => 'app.withering',
            'icon' => 'fab fa-pagelines'
        ],

        'infected' => [
            'localization' => 'app.infected',
            'icon' => 'fas fa-biohazard'
        ],

        'pest_infestation' => [
            'localization' => 'app.pest_infestation',
            'icon' => 'fas fa-bug'
        ],

        'transplant_shock' => [
            'localization' => 'app.transplant_shock',
            'icon' => 'fas fa-trailer'
        ],

        'nutritional_deficiency' => [
            'localization' => 'app.nutritional_deficiency',
            'icon' => 'fas fa-cookie'
        ],

        'sunburn' => [
            'localization' => 'app.sunburn',
            'icon' => 'fas fa-sun'
        ],

        'frostbite' => [
            'localization' => 'app.frostbite',
            'icon' => 'fas fa-snowflake'
        ],

        'root_rot' => [
            'localization' => 'app.root_rot',
            'icon' => 'fas fa-skull'
        ],
    ];

    static $lifespan_values = [
        'lifespan_annual',
        'lifespan_biennial',
        'lifespan_perennial'
    ];

    static $light_level_values = [
        'light_level_sunny',
        'light_level_half_shade',
        'light_level_filtered_light',
        'light_level_indirect_light',
        'light_level_full_shade',
        'light_level_darkness'
    ];

    //Columns offered on CSV export/import, in export column order. 'id' is prepended separately (see exportAllAsCsv/importFromCsv); 'location' is exported/imported by name rather than raw id.
    static $csv_columns = [
        'name',
        'scientific_name',
        'location',
        'tags',
        'last_watered',
        'last_repotted',
        'last_fertilised',
        'lifespan',
        'hardy',
        'cutting_month',
        'date_of_purchase',
        'humidity',
        'light_level',
        'health_state',
        'notes',
        'water_interval_days',
        'fertilise_interval_days',
        'repot_interval_days',
        'is_public'
    ];

    /**
     * @param $type
     * @return void
     * @throws \Exception
     */
    public static function validateSorting($type)
    {
        if (!in_array($type, static::$sorting_list)) {
            throw new \Exception('Invalid sorting type: ' . $type);
        }
    }

    /**
     * @param $dir
     * @return void
     * @throws \Exception
     */
    public static function validateDirection($dir)
    {
        if (!in_array($dir, static::$sorting_dir)) {
            throw new \Exception('Invalid sorting direction: ' . $dir);
        }
    }

    /**
     * @param $attribute
     * @return void
     * @throws \Exception
     */
    public static function validateAttribute($attribute)
    {
        if (!in_array($attribute, static::$allowed_attributes)) {
            throw new \Exception('Invalid attribute specified: ' . $attribute);
        }
    }

    /**
     * @param $location
     * @param $sorting
     * @param $direction
     * @return mixed
     * @throws \Exception
     */
    public static function getAll($location, $sorting = null, $direction = null)
    {
        try {
            if ($sorting === null) {
                $sorting = 'name';
            }

            if ($direction === null) {
                $direction = 'asc';
            }

            static::validateSorting($sorting);
            static::validateDirection($direction);

            return static::raw('SELECT * FROM `@THIS` WHERE location = ? AND history = 0 AND deleted_at IS NULL ORDER BY ' . $sorting . ' ' . $direction, [$location]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Every active (non-history) plant across every location, for the
     * all-plants browsing page - same sorting rules as getAll(), just
     * without the location filter.
     *
     * @param $sorting
     * @param $direction
     * @return mixed
     * @throws \Exception
     */
    public static function getAllPlants($sorting = null, $direction = null)
    {
        try {
            if ($sorting === null) {
                $sorting = 'name';
            }

            if ($direction === null) {
                $direction = 'asc';
            }

            static::validateSorting($sorting);
            static::validateDirection($direction);

            return static::raw('SELECT * FROM `@THIS` WHERE history = 0 AND deleted_at IS NULL ORDER BY ' . $sorting . ' ' . $direction);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $userId
     * @return mixed
     * @throws \Exception
     */
    public static function getAuthoredPlants($userId, $limit = 0)
    {
        try {
            if ($limit == 0) {
                return static::raw('SELECT * FROM `@THIS` WHERE last_edited_user = ? AND deleted_at IS NULL ORDER BY last_edited_date DESC', [$userId]);
            } else {
                return static::raw('SELECT * FROM `@THIS` WHERE last_edited_user = ? AND deleted_at IS NULL ORDER BY last_edited_date DESC LIMIT ' . $limit, [$userId]);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public static function getLastAddedPlants()
    {
        try {
            return static::raw('SELECT * FROM `@THIS` WHERE history = 0 AND deleted_at IS NULL ORDER BY id DESC LIMIT ' . strval(self::PLANT_LAST_UPDATED_AUTHORED_COUNT));
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public static function getLastAuthoredPlants()
    {
        try {
            return static::raw('SELECT * FROM `@THIS` WHERE history = 0 AND deleted_at IS NULL AND last_edited_user IS NOT NULL ORDER BY last_edited_date DESC LIMIT ' . strval(self::PLANT_LAST_UPDATED_AUTHORED_COUNT));
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
     * Plants shown on the public, unauthenticated catalogue (see
     * PublicController). Only plants explicitly marked is_public and
     * not archived to history show up there.
     *
     * @return mixed
     * @throws \Exception
     */
    public static function getPublicPlants()
    {
        try {
            return static::raw('SELECT * FROM `@THIS` WHERE is_public = 1 AND history = 0 AND deleted_at IS NULL ORDER BY name ASC');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return bool
     * @throws \Exception
     */
    public static function isPublic($id)
    {
        try {
            $plant = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
            return (($plant) && ($plant->get('is_public')));
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public static function getWarningPlants()
    {
        try {
            return static::raw('SELECT * FROM `@THIS` WHERE health_state <> \'in_good_standing\' AND history = 0 AND deleted_at IS NULL ORDER BY last_edited_date DESC');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Lists every (plant, care action) pair that is currently overdue,
     * i.e. has a configured interval and its last care date (or the
     * plant's creation date, if that care was never logged) plus that
     * interval has already passed. An interval of NULL or 0 means "no
     * reminder configured" for that action and is excluded. Archived
     * plants (history = 1) are excluded too.
     *
     * @return array a list of ['plant' => PlantsModel row, 'action' => string, 'due_since' => string, 'interval_days' => int]
     * @throws \Exception
     */
    public static function getCareDuePlants()
    {
        try {
            $due = [];

            foreach (static::$care_actions as $action => $cols) {
                $rows = static::raw('SELECT *, COALESCE(' . $cols['last'] . ', created_at) AS care_basis_date FROM `@THIS` WHERE history = 0 AND deleted_at IS NULL AND ' . $cols['interval'] . ' IS NOT NULL AND ' . $cols['interval'] . ' > 0 AND DATE_ADD(COALESCE(' . $cols['last'] . ', created_at), INTERVAL ' . $cols['interval'] . ' DAY) <= NOW()');

                foreach ($rows as $row) {
                    $due[] = [
                        'plant' => $row,
                        'action' => $action,
                        'due_since' => $row->get('care_basis_date'),
                        'interval_days' => $row->get($cols['interval'])
                    ];
                }
            }

            usort($due, function ($a, $b) {
                return strtotime($a['due_since']) <=> strtotime($b['due_since']);
            });

            return $due;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Cronjob endpoint: emails every opted-in user about plants that
     * are currently due for watering, fertilising or repotting.
     *
     * @return void
     * @throws \Exception
     */
    public static function cronjobCareReminder()
    {
        try {
            $due = static::getCareDuePlants();

            foreach ($due as $entry) {
                PlantCareInformerModel::inform($entry['plant'], $entry['action'], $entry['due_since'], env('APP_CRONJOB_MAILLIMIT', 5));
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Exports every active (non-history) plant as a CSV string, one row
     * per plant with a header row. The location column is exported as
     * its human-readable name (not the raw id) so the file round-trips
     * cleanly through importFromCsv().
     *
     * @return string
     * @throws \Exception
     */
    public static function exportAllAsCsv()
    {
        try {
            $plants = static::raw('SELECT * FROM `@THIS` WHERE history = 0 AND deleted_at IS NULL ORDER BY name ASC');

            $stream = fopen('php://temp', 'r+');

            fputcsv($stream, array_merge(['id'], static::$csv_columns));

            foreach ($plants as $plant) {
                $line = [$plant->get('id')];

                foreach (static::$csv_columns as $column) {
                    $line[] = ($column === 'location') ? LocationsModel::getNameById($plant->get('location')) : $plant->get($column);
                }

                fputcsv($stream, $line);
            }

            rewind($stream);
            $csv = stream_get_contents($stream);
            fclose($stream);

            return $csv;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Validates and coerces one parsed CSV row (column => raw string)
     * into a column => value map suitable for editPlantAttribute(). A
     * cell that doesn't fit its column's expected type/enum (or is
     * blank) is simply left out rather than failing the whole row, so
     * the row's other valid cells still get applied.
     *
     * @param $data
     * @return array
     * @throws \Exception
     */
    private static function sanitizeCsvRow($data)
    {
        $values = [];

        foreach (static::$csv_columns as $column) {
            if (!array_key_exists($column, $data)) {
                continue;
            }

            $value = trim($data[$column]);

            if ($value === '') {
                continue;
            }

            switch ($column) {
                case 'location':
                    $location_row = LocationsModel::raw('SELECT * FROM `@THIS` WHERE name = ?', [$value])->first();
                    if ($location_row) {
                        $values['location'] = $location_row->get('id');
                    }
                    break;

                case 'last_watered':
                case 'last_repotted':
                case 'last_fertilised':
                case 'date_of_purchase':
                    $timestamp = strtotime($value);
                    if ($timestamp !== false) {
                        $values[$column] = date('Y-m-d H:i:s', $timestamp);
                    }
                    break;

                case 'hardy':
                case 'is_public':
                    $values[$column] = (in_array(strtolower($value), ['1', 'true', 'yes', 'y'])) ? 1 : 0;
                    break;

                case 'cutting_month':
                    if ((is_numeric($value)) && ((int)$value >= 1) && ((int)$value <= 12)) {
                        $values[$column] = (int)$value;
                    }
                    break;

                case 'humidity':
                case 'water_interval_days':
                case 'fertilise_interval_days':
                case 'repot_interval_days':
                    if ((is_numeric($value)) && ((int)$value >= 0)) {
                        $values[$column] = (int)$value;
                    }
                    break;

                case 'lifespan':
                    if (in_array($value, static::$lifespan_values)) {
                        $values[$column] = $value;
                    }
                    break;

                case 'light_level':
                    if (in_array($value, static::$light_level_values)) {
                        $values[$column] = $value;
                    }
                    break;

                case 'health_state':
                    if (array_key_exists($value, static::$plant_health_states)) {
                        $values[$column] = $value;
                    }
                    break;

                default:
                    $values[$column] = $value;
            }
        }

        return $values;
    }

    /**
     * Imports plants from an uploaded CSV file (as produced by
     * exportAllAsCsv(), or hand-edited to match its columns). Each row
     * is matched to an existing active plant by its 'id' column, when
     * present and valid; rows without a matching id are created as new
     * plants instead, provided they have a 'name' and a 'location' that
     * resolves to an existing location by name. Unknown columns are
     * ignored. Every applied cell goes through the same
     * editPlantAttribute()/addPlant() path as a manual edit, so it's
     * logged and (for health_state) recorded to the health history the
     * same way.
     *
     * @param $tmpFilePath path to the uploaded CSV file (e.g. $_FILES[...]['tmp_name'])
     * @return array ['created' => int, 'updated' => int, 'errors' => string[]]
     * @throws \Exception
     */
    public static function importFromCsv($tmpFilePath)
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            $stream = fopen($tmpFilePath, 'r');
            if (!$stream) {
                throw new \Exception('Could not read the uploaded file');
            }

            $header = fgetcsv($stream);
            if (!is_array($header)) {
                fclose($stream);
                throw new \Exception('The file is empty or not a valid CSV');
            }

            $header = array_map(function ($col) {
                return strtolower(trim($col));
            }, $header);

            $created = 0;
            $updated = 0;
            $errors = [];
            $row_num = 1;

            while (($row = fgetcsv($stream)) !== false) {
                $row_num++;

                if (count($row) !== count($header)) {
                    $errors[] = 'Row ' . $row_num . ': column count does not match the header';
                    continue;
                }

                $data = array_combine($header, $row);
                $values = static::sanitizeCsvRow($data);

                $plant_id = ((isset($data['id'])) && (is_numeric($data['id'])) && ((int)$data['id'] > 0)) ? (int)$data['id'] : null;
                $existing = ($plant_id) ? static::raw('SELECT * FROM `@THIS` WHERE id = ? AND history = 0 AND deleted_at IS NULL', [$plant_id])->first() : null;

                if ($existing) {
                    foreach ($values as $column => $value) {
                        static::editPlantAttribute($existing->get('id'), $column, $value);
                    }

                    $updated++;
                } else {
                    if ((!isset($values['name'])) || (strlen($values['name']) === 0)) {
                        $errors[] = 'Row ' . $row_num . ': a name is required to create a new plant';
                        continue;
                    }

                    if (!isset($values['location'])) {
                        $errors[] = 'Row ' . $row_num . ': "' . htmlspecialchars(trim($data['location'] ?? ''), ENT_QUOTES) . '" is not a known location, so this plant could not be created';
                        continue;
                    }

                    $new_id = static::addPlant($values['name'], $values['location']);

                    unset($values['name'], $values['location']);

                    foreach ($values as $column => $value) {
                        static::editPlantAttribute($new_id, $column, $value);
                    }

                    $created++;
                }
            }

            fclose($stream);

            return ['created' => $created, 'updated' => $updated, 'errors' => $errors];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $year
     * @param $limit
     * @param $sorting
     * @param $direction
     * @return mixed
     * @throws \Exception
     */
    public static function getHistory($year = null, $limit = null, $sorting = null, $direction = null)
    {
        try {
            if ($sorting === null) {
                $sorting = 'history_date';
            }

            if ($direction === null) {
                $direction = 'desc';
            }

            static::validateSorting($sorting);
            static::validateDirection($direction);

            $strlimit = '';
            if ($limit) {
                $strlimit = ' LIMIT ' . $limit;
            }

            if ($year !== null) {
                return static::raw('SELECT * FROM `@THIS` WHERE YEAR(history_date) = ? AND history = 1 AND deleted_at IS NULL ORDER BY ' . $sorting . ' ' . $direction . $strlimit, [$year]);
            } else {
                return static::raw('SELECT * FROM `@THIS` WHERE history = 1 AND deleted_at IS NULL ORDER BY ' . $sorting . ' ' . $direction . $strlimit);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public static function getHistoryYears()
    {
        try {
            return static::raw('SELECT DISTINCT YEAR(history_date) AS history_year FROM `@THIS` WHERE history = 1 AND deleted_at IS NULL AND history_date IS NOT NULL ORDER BY YEAR(history_date) DESC');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $name
     * @param $location
     * @param $api
     * @return int
     * @throws \Exception
     */
    public static function addPlant($name, $location, $api = false)
    {
        try {
            $user = null;
            
            if (!$api) {
                $user = UserModel::getAuthUser();
                if (!$user) {
                    throw new \Exception('Invalid user');
                }
            }

            if ((isset($_FILES['photo'])) && ($_FILES['photo']['error'] === UPLOAD_ERR_OK)) {
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
            } else {
                $fullFileName = self::PLANT_PLACEHOLDER_FILE;
            }
            
            static::raw('INSERT INTO `@THIS` (name, location, photo, last_edited_user, last_edited_date) VALUES(?, ?, ?, ?, CURRENT_TIMESTAMP)', [
                $name, $location, $fullFileName, $user?->get('id')
            ]);
            
            $query = static::raw('SELECT * FROM `@THIS` ORDER BY id DESC LIMIT 1')->first();

            PlantHealthLogModel::addEntry($query->get('id'), self::PLANT_STATE_GOOD);

            if (!$api) {
                TextBlockModule::newPlant($name, url('/plants/details/' . $query->get('id')));
                LogModel::addLog($user->get('id'), $location, 'add_plant', $name, url('/plants/details/' . $query->get('id')));
            }

            return $query->get('id');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $plantId
     * @param $attribute
     * @param $value
     * @param $api
     * @return void
     * @throws \Exception
     */
    public static function editPlantAttribute($plantId, $attribute, $value, $api = false)
    {
        try {
            $user = null;
            
            if (!$api) {
                $user = UserModel::getAuthUser();
                if (!$user) {
                    throw new \Exception('Invalid user');
                }
            }

            static::validateAttribute($attribute);

            $previous_health_state = null;
            if ($attribute === 'health_state') {
                $current_plant = static::raw('SELECT health_state FROM `@THIS` WHERE id = ?', [$plantId])->first();
                $previous_health_state = ($current_plant) ? $current_plant->get('health_state') : null;
            }

            static::raw('UPDATE `@THIS` SET ' . $attribute . ' = ?, last_edited_user = ?, last_edited_date = CURRENT_TIMESTAMP WHERE id = ?', [($value !== '#null') ? $value : null, $user?->get('id'), $plantId]);

            if (!$api) {
                LogModel::addLog($user->get('id'), $plantId, $attribute, $value, url('/plants/details/' . $plantId));
            }

            if (app('system_message_plant_log')) {
                PlantLogModel::addEntry($plantId, $attribute . ' = ' . $value, '', '', true, true);
            }

            if (($attribute === 'health_state') && ($value !== $previous_health_state)) {
                PlantHealthLogModel::addEntry($plantId, $value);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Updates one of the plant's tracked care-date attributes
     * (last_watered / last_repotted / last_fertilised) from a journal
     * entry, e.g. via a "mark as watered" checkbox on the entry form.
     * Only ever moves the date forward: if the plant already has a
     * newer date on file, an older/backfilled journal entry won't
     * regress it. Does not write a log entry or a system journal entry
     * of its own, since the journal entry that triggered this already
     * documents the change.
     *
     * @param $plantId
     * @param $attribute one of 'last_watered', 'last_repotted', 'last_fertilised'
     * @param $date a Y-m-d or Y-m-d H:i:s datetime string
     * @return void
     * @throws \Exception
     */
    public static function updateDateAttributeIfNewer($plantId, $attribute, $date)
    {
        try {
            if (!in_array($attribute, ['last_watered', 'last_repotted', 'last_fertilised'])) {
                throw new \Exception('Invalid attribute: ' . $attribute);
            }

            $plant = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$plantId])->first();
            if (!$plant) {
                throw new \Exception('Invalid plant: ' . $plantId);
            }

            $current = $plant->get($attribute);

            if ((!$current) || (strtotime($date) >= strtotime($current))) {
                static::raw('UPDATE `@THIS` SET ' . $attribute . ' = ? WHERE id = ?', [$date, $plantId]);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $plantId
     * @param $text
     * @param $link
     * @return void
     * @throws \Exception
     */
    public static function editPlantLink($plantId, $text, $link = '')
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            if (strlen($link) > 0) {
                if ((strpos($link, 'http://') === false) && (strpos($link, 'https://') === false)) {
                    $link = '';
                }
            }
            
            static::raw('UPDATE `@THIS` SET scientific_name = ?, knowledge_link = ?, last_edited_user = ?, last_edited_date = CURRENT_TIMESTAMP WHERE id = ?', [$text, $link, $user->get('id'), $plantId]);
        
            LogModel::addLog($user->get('id'), $plantId, 'scientific_name|knowledge_link', $text . '|' . ((strlen($link) > 0) ? $link : 'null'), url('/plants/details/' . $plantId));
        
            if (app('system_message_plant_log')) {
                PlantLogModel::addEntry($plantId, 'scientific_name = ' . $text, '', '', false, true);

                if (strlen($link) > 0) {
                    PlantLogModel::addEntry($plantId, 'knowledge_link = ' . $link, '', '', false, true);
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $plantId
     * @param $attribute
     * @param $value
     * @param $api
     * @return void
     * @throws \Exception
     */
    public static function editPlantPhoto($plantId, $attribute, $value, $api = false)
    {
        try {
            $user = null;

            if (!$api) {
                $user = UserModel::getAuthUser();
                if (!$user) {
                    throw new \Exception('Invalid user');
                }
            }

            static::validateAttribute($attribute);

            if ((!isset($_FILES[$value])) || ($_FILES[$value]['error'] !== UPLOAD_ERR_OK)) {
                throw new \Exception('Errorneous file');
            }

            $file_ext = UtilsModule::getImageExt($_FILES[$value]['tmp_name']);

            if ($file_ext === null) {
                throw new \Exception('File is not a valid image');
            }

            $file_name = md5(random_bytes(55) . date('Y-m-d H:i:s'));

            move_uploaded_file($_FILES[$value]['tmp_name'], public_path('/img/' . $file_name . '.' . $file_ext));

            $img_type = UtilsModule::getImageType($file_ext, public_path('/img/' . $file_name));

            UtilsModule::optimizeImage(public_path('/img/' . $file_name . '.' . $file_ext), $img_type);

            if (!UtilsModule::createThumbFile(public_path('/img/' . $file_name . '.' . $file_ext), $img_type, public_path('/img/' . $file_name), $file_ext)) {
                throw new \Exception('createThumbFile failed');
            }

            static::raw('UPDATE `@THIS` SET ' . $attribute . ' = ?, last_edited_user = ?, last_edited_date = CURRENT_TIMESTAMP, last_photo_date = CURRENT_TIMESTAMP WHERE id = ?', [$file_name . '_thumb.' . $file_ext, $user?->get('id'), $plantId]);
        
            if (!$api) {
                LogModel::addLog($user->get('id'), $plantId, $attribute, $value, url('/plants/details/' . $plantId));
            }

            if (app('system_message_plant_log')) {
                PlantLogModel::addEntry($plantId, $attribute . ' = ' . $file_name . '.' . $file_ext, '', '', $api, true);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $plantId
     * @param $attribute
     * @param $value
     * @param $api
     * @return void
     * @throws \Exception
     */
    public static function editPlantPhotoURL($plantId, $attribute, $value, $api = false)
    {
        try {
            $user = null;

            if (!$api) {
                $user = UserModel::getAuthUser();
                if (!$user) {
                    throw new \Exception('Invalid user');
                }
            }

            static::validateAttribute($attribute);

            static::raw('UPDATE `@THIS` SET ' . $attribute . ' = ?, last_edited_user = ?, last_edited_date = CURRENT_TIMESTAMP, last_photo_date = CURRENT_TIMESTAMP WHERE id = ?', [$value, $user?->get('id'), $plantId]);
        
            if (!$api) {
                LogModel::addLog($user->get('id'), $plantId, $attribute, $value, url('/plants/details/' . $plantId));
            }

            if (app('system_message_plant_log')) {
                PlantLogModel::addEntry($plantId, $attribute . ' = ' . $value, '', '', $api, true);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $plantId
     * @return void
     * @throws \Exception
     */
    public static function clearPreviewPhoto($plantId)
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            $item = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$plantId])->first();

            if ($item->get('photo') === self::PLANT_PLACEHOLDER_FILE) {
                throw new \Exception('There is no preview photo set');
            }

            static::raw('UPDATE `@THIS` SET photo = ?, last_edited_user = ?, last_edited_date = CURRENT_TIMESTAMP WHERE id = ?', [self::PLANT_PLACEHOLDER_FILE, $user->get('id'), $plantId]);
        
            if (file_exists(public_path() . '/img/' . $item->get('photo'))) {
                unlink(public_path() . '/img/' . $item->get('photo'));
            }
            
            if (file_exists(public_path() . '/img/' . str_replace('_thumb', '', $item->get('photo')))) {
                unlink(public_path() . '/img/' . str_replace('_thumb', '', $item->get('photo')));
            }

            LogModel::addLog($user->get('id'), $plantId, 'photo', self::PLANT_PLACEHOLDER_FILE, url('/plants/details/' . $plantId));

            if (app('system_message_plant_log')) {
                PlantLogModel::addEntry($plantId, 'photo = ' . self::PLANT_PLACEHOLDER_FILE, '', '', false, true);
            }
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
            return static::raw('SELECT COUNT(*) as count FROM `@THIS` WHERE history = 0 AND deleted_at IS NULL')->first()->get('count');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $text
     * @param $search_name
     * @param $search_scientific_name
     * @param $search_tags
     * @param $search_notes
     * @return mixed
     * @throws \Exception
     */
    public static function performSearch($text, $search_name, $search_scientific_name, $search_tags, $search_notes)
    {
        try {
            $text = trim(strtolower($text));

            // Every branch below is AND-ed with "not in the recycle bin" -
            // the OR'd name/scientific_name/tags/notes conditions are
            // grouped in parens so that AND doesn't only bind to the first
            // of them (operator precedence would otherwise let a trashed
            // plant slip back in through the second/third/fourth OR arm).
            $query = 'SELECT * FROM `@THIS` WHERE deleted_at IS NULL';
            $args = [];

            if (substr($text, 0, 1) === '#') {
                $text = ltrim(substr($text, 1), '0');

                return static::raw('SELECT * FROM `@THIS` WHERE deleted_at IS NULL AND id = ? LIMIT 1', [$text]);
            }

            $conditions = [];

            if ($search_name) {
                $conditions[] = 'LOWER(name) LIKE ?';
                $args[] = '%' . $text . '%';
            }

            if ($search_scientific_name) {
                $conditions[] = 'LOWER(scientific_name) LIKE ?';
                $args[] = '%' . $text . '%';
            }

            if ($search_tags) {
                $conditions[] = 'LOWER(tags) LIKE ?';
                $args[] = '%' . $text . '%';
            }

            if ($search_notes) {
                $conditions[] = 'LOWER(notes) LIKE ?';
                $args[] = '%' . $text . '%';
            }

            if (count($conditions) > 0) {
                $query .= ' AND (' . implode(' OR ', $conditions) . ')';
            }

            $query .= ' ORDER BY last_edited_date DESC';

            return static::raw($query, $args);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $location
     * @return void
     * @throws \Exception
     */
    public static function updateLastWatered($location)
    {
        try {
            static::raw('UPDATE `@THIS` SET last_watered = CURRENT_TIMESTAMP WHERE location = ? AND deleted_at IS NULL', [$location]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $location
     * @return void
     * @throws \Exception
     */
    public static function updateLastRepotted($location)
    {
        try {
            static::raw('UPDATE `@THIS` SET last_repotted = CURRENT_TIMESTAMP WHERE location = ? AND deleted_at IS NULL', [$location]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $location
     * @return void
     * @throws \Exception
     */
    public static function updateLastFertilised($location)
    {
        try {
            static::raw('UPDATE `@THIS` SET last_fertilised = CURRENT_TIMESTAMP WHERE location = ? AND deleted_at IS NULL', [$location]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $plantId
     * @return void
     * @throws \Exception
     */
    public static function markHistorical($plantId)
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            $plant = PlantsModel::getDetails($plantId);

            static::raw('UPDATE `@THIS` SET history = 1, history_date = CURRENT_TIMESTAMP WHERE id = ?', [$plantId]);

            LogModel::addLog($user->get('id'), $plant->get('name'), 'mark_historical', '', url('/plants/history'));
            TextBlockModule::plantToHistory($plant->get('name'), url('/plants/history'));

            if (app('system_message_plant_log')) {
                PlantLogModel::addEntry($plantId, 'history = 1', '', '', false, true);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $plantId
     * @return void
     * @throws \Exception
     */
    public static function unmarkHistorical($plantId)
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            $plant = PlantsModel::getDetails($plantId);

            static::raw('UPDATE `@THIS` SET history = 0, history_date = NULL WHERE id = ?', [$plantId]);

            LogModel::addLog($user->get('id'), $plant->get('name'), 'historical_restore', '', url('/plants/details/' . $plantId));
            TextBlockModule::plantFromHistory($plant->get('name'), url('/plants/details/' . $plantId));

            if (app('system_message_plant_log')) {
                PlantLogModel::addEntry($plantId, 'history = 0', '', '', false, true);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Moves a plant to the recycle bin rather than deleting it outright -
     * photos, attachments and the row itself are all left in place so
     * restorePlant() can bring it back exactly as it was. Actual removal
     * only happens via purgePlant(), once someone empties the bin.
     *
     * @param $plantId
     * @return void
     * @throws \Exception
     */
    public static function removePlant($plantId)
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            $plant = PlantsModel::getDetails($plantId);
            if (!$plant) {
                throw new \Exception('Plant with ID not found: ' . $plantId);
            }

            static::raw('UPDATE `@THIS` SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?', [$plantId]);

            LogModel::addLog($user->get('id'), $plant->get('name'), 'trash_plant', '');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Takes a plant back out of the recycle bin.
     *
     * @param $plantId
     * @return void
     * @throws \Exception
     */
    public static function restorePlant($plantId)
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            $plant = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$plantId])->first();
            if (!$plant) {
                throw new \Exception('Plant with ID not found: ' . $plantId);
            }

            static::raw('UPDATE `@THIS` SET deleted_at = NULL WHERE id = ?', [$plantId]);

            LogModel::addLog($user->get('id'), $plant->get('name'), 'restore_plant', '');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Permanently deletes a plant that's already in the recycle bin - this
     * is the old removePlant() body: photo files, gallery photos and
     * attachments are all actually erased here, since there's no going
     * back from this one. Only ever call this on a plant whose
     * deleted_at is already set - purging straight out of the active
     * list would skip the "are you sure" step the recycle bin exists for.
     *
     * @param $plantId
     * @return void
     * @throws \Exception
     */
    public static function purgePlant($plantId)
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            $plant = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$plantId])->first();
            if (!$plant) {
                throw new \Exception('Plant with ID not found: ' . $plantId);
            }

            if (!$plant->get('deleted_at')) {
                throw new \Exception('Plant is not in the recycle bin: ' . $plantId);
            }

            if ($plant->get('photo') !== self::PLANT_PLACEHOLDER_FILE) {
                if (file_exists(public_path('/img/' . $plant->get('photo')))) {
                    unlink(public_path('/img/' . $plant->get('photo')));
                }

                $original_photo = str_replace('_thumb', '', $plant->get('photo'));

                if (file_exists(public_path('/img/' . $original_photo))) {
                    unlink(public_path('/img/' . $original_photo));
                }
            }

            PlantPhotoModel::clearForPlant($plantId);
            PlantAttachmentModel::clearForPlant($plantId, $plant->get('clone_num') == null);

            static::raw('DELETE FROM `@THIS` WHERE id = ?', [$plantId]);

            LogModel::addLog($user->get('id'), $plant->get('name'), 'remove_plant', '');
            TextBlockModule::deletePlant($plant->get('name'));
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Every plant currently sitting in the recycle bin, most recently
     * deleted first.
     *
     * @return mixed
     * @throws \Exception
     */
    public static function getTrash()
    {
        try {
            return static::raw('SELECT * FROM `@THIS` WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Permanently deletes everything currently in the recycle bin.
     *
     * @return void
     * @throws \Exception
     */
    public static function emptyTrash()
    {
        try {
            $trashed = static::getTrash();

            if (is_countable($trashed)) {
                foreach ($trashed as $plant) {
                    static::purgePlant($plant->get('id'));
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $from
     * @param $to
     * @return void
     * @throws \Exception
     */
    public static function migratePlants($from, $to)
    {
        try {
            static::raw('UPDATE `@THIS` SET location = ? WHERE location = ?', [
                $to, $from
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $plantId
     * @return void
     * @throws \Exception
     */
    public static function setUpdated($plantId)
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            static::raw('UPDATE `@THIS` SET last_edited_user = ?, last_edited_date = CURRENT_TIMESTAMP WHERE id = ?', [
                $user->get('id'), (int)$plantId
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return int
     * @throws \Exception
     */
    public static function getPlantCount($id)
    {
        try {
            return static::raw('SELECT COUNT(*) AS count FROM `@THIS` WHERE location = ? AND history = 0 AND deleted_at IS NULL', [$id])->first()->get('count');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return int
     * @throws \Exception
     */
    public static function getDangerCount($id)
    {
        try {
            return static::raw('SELECT COUNT(*) AS count FROM `@THIS` WHERE location = ? AND health_state <> ? AND history = 0 AND deleted_at IS NULL', [
                $id, 'in_good_standing'
            ])->first()->get('count');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return int
     * @throws \Exception
     */
    public static function clonePlant($id)
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            $source = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
            if (!$source) {
                throw new \Exception('Plant with ID not found: ' . $id);
            }

            $updated_tags = $source->get('tags');

            if (strpos($source->get('tags'), strtolower($source->get('name'))) === false) {
                $updated_tags = $source->get('tags') . ' ' . strtolower($source->get('name'));

                static::raw('UPDATE `@THIS` SET tags = ?, clone_num = ? WHERE id = ?', [
                    $updated_tags, 0, $source->get('id')
                ]);
            }

            if (file_exists(public_path() . '/img/' . $source->get('photo'))) {
                $target_base_name = md5(random_bytes(55) . date('Y-m-d H:i:s'));
                $target_thumb_name = $target_base_name . '_thumb.' . pathinfo($source->get('photo'), PATHINFO_EXTENSION);
                $target_original_name = $target_base_name . '.' . pathinfo($source->get('photo'), PATHINFO_EXTENSION);
                copy(public_path() . '/img/' . $source->get('photo'), public_path() . '/img/' . $target_thumb_name);
                copy(public_path() . '/img/' . str_replace('_thumb', '', $source->get('photo')), public_path() . '/img/' . $target_original_name);
            } else {
                $target_thumb_name = $source->get('photo');
            }

            $clone_origin = null;
            if (($source->get('clone_num')) && (static::getDetails($source->get('clone_origin')))) {
                $clone_origin = $source->get('clone_origin');
            } else {
                $clone_origin = $source->get('id');

                static::raw('UPDATE `@THIS` SET clone_origin = NULL, clone_num = 0 WHERE id = ?', [$source->get('id')]);
            }

            static::raw('INSERT INTO `@THIS` (name, scientific_name, knowledge_link, tags, location, photo, last_watered, last_repotted, last_fertilised, lifespan, hardy, cutting_month, date_of_purchase, humidity, light_level, health_state, notes, last_edited_user, last_edited_date, clone_num, clone_origin) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                $source->get('name'), $source->get('scientific_name'), $source->get('knowledge_link'), $updated_tags, $source->get('location'), $target_thumb_name, $source->get('last_watered'), $source->get('last_repotted'), $source->get('last_fertilised'), $source->get('lifespan'), $source->get('hardy'), $source->get('cutting_month'), $source->get('date_of_purchase'), $source->get('humidity'), $source->get('light_level'), $source->get('health_state'), $source->get('notes'), $user->get('id'), date('Y-m-d H:i:s'), static::getNameCount($source->get('name')), $clone_origin
            ]);

            $clone = static::raw('SELECT * FROM `@THIS` ORDER BY id DESC LIMIT 1')->first();

            CustPlantAttrModel::cloneAttributes($source->get('id'), $clone->get('id'));
            PlantAttachmentModel::cloneAttachments($source->get('id'), $clone->get('id'));

            return $clone->get('id');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public static function findOffspring($id)
    {
        try {
            return static::raw('SELECT * FROM `@THIS` WHERE clone_origin = ? AND deleted_at IS NULL', [$id]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return int
     * @throws \Exception
     */
    public static function offspringCount($id)
    {
        try {
            return (int)static::raw('SELECT COUNT(*) AS `count` FROM `@THIS` WHERE clone_origin = ? AND deleted_at IS NULL', [$id])?->first()?->get('count');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Plants that list this one as their parent_plant - i.e. propagated
     * from it. Separate from findOffspring()/offspringCount() above,
     * which walk clone_origin (the full-record "Clone Plant" feature)
     * instead - a plant can be a propagation child here without being a
     * clone at all.
     *
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public static function getPropagatedChildren($id)
    {
        try {
            return static::raw('SELECT * FROM `@THIS` WHERE parent_plant = ? AND history = 0 AND deleted_at IS NULL ORDER BY name ASC', [$id]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $name
     * @return int
     * @throws \Exception
     */
    public static function getNameCount($name)
    {
        try {
            $result = static::raw('SELECT COUNT(name) AS `count` FROM `@THIS` WHERE name = ?', [$name])->first();
            return $result->get('count');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public static function generateQRCode($id)
    {
        try {
            $plant = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
            if (!$plant) {
                throw new \Exception('Plant not found: ' . $id);
            }

            $options = new QROptions();
            $options->invertMatrix = true;

            $oqr = new QRCode($options);
			return $oqr->render(url('/plants/details/' . $plant->get('id')));
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $location
     * @param $limit
     * @param $from
     * @param $sort
     * @return mixed
     * @throws \Exception
     */
    public static function getPlantList($location, $limit = null, $from = null, $sort = null)
    {
        try {
            if (($location === null) || (!is_numeric($location))) {
                throw new \Exception('Invalid location ID: ' . print_r($location, true));
            }

            if ($limit !== null) {
                if (is_numeric($limit)) {
                    $limit = ' LIMIT ' . $limit;
                } else {
                    throw new \Exception('Invalid expression for limit: ' . print_r($limit, true));
                }
            }

            if ($sort !== null) {
                if ($sort === 'asc') {
                    $sort = ' ORDER BY id ASC ';
                } else if ($sort === 'desc') {
                    $sort = ' ORDER BY id DESC ';
                } else {
                    throw new \Exception('Invalid expression for sort: ' . print_r($sort, true));
                }
            }

            if (($from !== null) && (is_numeric($from))) {
                return static::raw('SELECT * FROM `@THIS` WHERE location = ? AND id > ? AND deleted_at IS NULL' . $sort . $limit, [$location, $from]);
            } else {
                return static::raw('SELECT * FROM `@THIS` WHERE location = ? AND deleted_at IS NULL' . $sort . $limit, [$location]);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $location
     * @param $include
     * @return mixed
     * @throws \Exception
     */
    public static function getSpecificInfo($location, $include = 'id')
    {
        try {
            return static::raw('SELECT ' . $include . ' FROM `@THIS` WHERE location = ? AND history = 0 AND deleted_at IS NULL', [$location]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    public static function getDistinctTags()
    {
        try {
            $sql = '
                WITH RECURSIVE tag_split AS (
                    SELECT 
                        SUBSTRING_INDEX(tags, " ", 1) AS tag,
                        SUBSTRING(tags, LOCATE(" ", tags) + 1) AS remaining
                    FROM `@THIS`
                    WHERE tags <> ""

                    UNION ALL

                    SELECT 
                        SUBSTRING_INDEX(remaining, " ", 1),
                        SUBSTRING(remaining, LOCATE(" ", remaining) + 1)
                    FROM tag_split
                    WHERE remaining LIKE "% %"
                )
                SELECT DISTINCT tag FROM tag_split WHERE tag <> "";
            ';

            return static::raw($sql);
        } catch (\Exception) {
            throw $e;
        }
    }
}