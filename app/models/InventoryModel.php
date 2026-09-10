<?php

use chillerlan\QRCode\{QRCode, QROptions};

/**
 * Class InventoryModel
 * 
 * Manages inventory data
 */ 
class InventoryModel extends \Asatru\Database\Model {
    public static $supported_exports = [
        'json' => [
            'label' => 'JSON',
            'method' => 'exportItemsAsJson'
        ],

        'csv' => [
            'label' => 'CSV',
            'method' => 'exportItemsAsCsv'
        ],

        'pdf' => [
            'label' => 'PDF',
            'method' => 'exportItemsAsPdf'
        ]
    ];

    /**
     * @param $name
     * @param $description
     * @param $tags
     * @param $location
     * @param $amount
     * @param $group
     * @param $photo
     * @param $api
     * @param $location_id A real LocationsModel id to scope this item to, or 'unassigned'/null to leave unassigned
     * @return int
     * @throws \Exception
     */
    public static function addItem($name, $description, $tags, $location, $amount, $group, $photo, $api = false, $location_id = null)
    {
        try {
            $user = UserModel::getAuthUser();
            if ((!$user) && (!$api)) {
                throw new \Exception('Invalid user');
            }

            if (!InvGroupModel::isValidGroupToken($group)) {
                throw new \Exception('Invalid group token: ' . $group);
            }

            if ($location_id === 'unassigned') {
                $location_id = null;
            }

            static::raw('INSERT INTO `@THIS` (name, group_ident, description, tags, location, location_id, amount, last_edited_user, last_edited_date) VALUES(?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)', [
                $name, $group, $description, trim($tags), $location, ($location_id ?: null), $amount, (($user) ? $user->get('id') : 0)
            ]);

            $row = static::raw('SELECT * FROM `@THIS` ORDER BY id DESC LIMIT 1')->first();

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

                static::raw('UPDATE `@THIS` SET photo = ? WHERE id = ?', [
                    $file_name . '_thumb.' . $file_ext, $row->get('id')
                ]);
            } else {
                if ((is_string($photo)) && ((strpos($photo, 'http://') === 0) || (strpos($photo, 'https://') === 0))) {
                    static::raw('UPDATE `@THIS` SET photo = ? WHERE id = ?', [
                        $photo, $row->get('id')
                    ]);
                }
            }

            if (!$api) {
                LogModel::addLog($user->get('id'), 'inventory', 'add_inventory_item', $name, url('/inventory?expand=' . $row->get('id') . '#anchor-item-' . $row->get('id')));
                TextBlockModule::createdInventoryItem($name, url('/inventory?expand=' . $row->get('id') . '#anchor-item-' . $row->get('id')));
            }

            return $row->get('id');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @param $name
     * @param $description
     * @param $tags
     * @param $location
     * @param $amount
     * @param $group
     * @param $photo
     * @param $api
     * @param $location_id A real LocationsModel id to (re)assign this item to, 'unassigned' to explicitly clear it, or null to leave it untouched (used by the API, which may not know about locations)
     * @return void
     * @throws \Exception
     */
    public static function editItem($id, $name, $description, $tags, $location, $amount, $group, $photo, $api = false, $location_id = null)
    {
        try {
            $user = UserModel::getAuthUser();
            if ((!$user) && (!$api)) {
                throw new \Exception('Invalid user');
            }

            $row = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
            if (!$row) {
                throw new \Exception('Invalid item: ' . $id);
            }

            if ($location_id !== null) {
                if ($location_id === 'unassigned') {
                    $location_id = null;
                }

                static::raw('UPDATE `@THIS` SET name = ?, group_ident = ?, location = ?, location_id = ?, description = ?, tags = ?, amount = ? WHERE id = ?', [
                    $name, $group, $location, $location_id, $description, $tags, $amount, $row->get('id')
                ]);
            } else {
                static::raw('UPDATE `@THIS` SET name = ?, group_ident = ?, location = ?, description = ?, tags = ?, amount = ? WHERE id = ?', [
                    $name, $group, $location, $description, $tags, $amount, $row->get('id')
                ]);
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

                if (is_string($row->get('photo'))) {
                    $oldThumbFile = public_path('/img/' . $row->get('photo'));

                    if (file_exists($oldThumbFile)) {
                        unlink($oldThumbFile);
                    }

                    $oldOrigFile = public_path('/img/' . str_replace('_thumb', '', $row->get('photo')));

                    if (file_exists($oldOrigFile)) {
                        unlink($oldOrigFile);
                    }
                }

                static::raw('UPDATE `@THIS` SET photo = ? WHERE id = ?', [
                    $file_name . '_thumb.' . $file_ext, $row->get('id')
                ]);
            } else {
                if ((is_string($photo)) && ((strpos($photo, 'http://') === 0) || (strpos($photo, 'https://') === 0))) {
                    static::raw('UPDATE `@THIS` SET photo = ? WHERE id = ?', [
                        $photo, $row->get('id')
                    ]);
                }
            }

            static::raw('UPDATE `@THIS` SET last_edited_user = ?, last_edited_date = CURRENT_TIMESTAMP WHERE id = ?', [
                (($user) ? $user->get('id') : 0), $row->get('id')
            ]);

            if (!$api) {
                LogModel::addLog($user->get('id'), 'inventory', 'edit_inventory_item', $name, url('/inventory?expand=' . $row->get('id') . '#anchor-item-' . $row->get('id')));
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @param $api
     * @return int
     * @throws \Exception
     */
    public static function incAmount($id, $api = false)
    {
        try {
            $user = UserModel::getAuthUser();
            if ((!$user) && (!$api)) {
                throw new \Exception('Invalid user');
            }

            $row = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
            if (!$row) {
                throw new \Exception('Invalid item: ' . $id);
            }

            $amount = $row->get('amount') + 1;
            
            static::raw('UPDATE `@THIS` SET amount = ?, last_edited_user = ?, last_edited_date = CURRENT_TIMESTAMP WHERE id = ?', [
                $amount, (($user) ? $user->get('id') : 0), $row->get('id')
            ]);

            if (!$api) {
                LogModel::addLog($user->get('id'), 'inventory', 'increment_inventory_item', $row->get('name'), url('/inventory?expand=' . $row->get('id') . '#anchor-item-' . $row->get('id')));
            }

            return $amount;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @param $api
     * @return int
     * @throws \Exception
     */
    public static function decAmount($id, $api = false)
    {
        try {
            $user = UserModel::getAuthUser();
            if ((!$user) && (!$api)) {
                throw new \Exception('Invalid user');
            }

            $row = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
            if (!$row) {
                throw new \Exception('Invalid item: ' . $id);
            }

            $amount = $row->get('amount') - 1;
            if ($amount < 0) {
                $amount = 0;
            }
            
            static::raw('UPDATE `@THIS` SET amount = ?, last_edited_user = ?, last_edited_date = CURRENT_TIMESTAMP WHERE id = ?', [
                $amount, (($user) ? $user->get('id') : 0), $row->get('id')
            ]);

            if (!$api) {
                LogModel::addLog($user->get('id'), 'inventory', 'decrement_inventory_item', $row->get('name'), url('/inventory?expand=' . $row->get('id') . '#anchor-item-' . $row->get('id')));
            }

            return $amount;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public static function getInventory()
    {
        try {
            return static::raw('SELECT * FROM `@THIS` ORDER BY group_ident, name ASC');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public static function getItemById($id)
    {
        try {
            return static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $locationId
     * @return mixed
     * @throws \Exception
     */
    public static function getByLocation($locationId)
    {
        try {
            return static::raw('SELECT * FROM `@THIS` WHERE location_id = ? ORDER BY group_ident, name ASC', [$locationId]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Items that have never been assigned a Location (e.g. from before
     * per-location inventories existed).
     *
     * @return mixed
     * @throws \Exception
     */
    public static function getUnassigned()
    {
        try {
            return static::raw('SELECT * FROM `@THIS` WHERE location_id IS NULL ORDER BY group_ident, name ASC');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $locationId
     * @return int
     * @throws \Exception
     */
    public static function getCountByLocation($locationId)
    {
        try {
            return (int)static::raw('SELECT COUNT(*) as count FROM `@THIS` WHERE location_id = ?', [$locationId])->first()->get('count');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return int
     * @throws \Exception
     */
    public static function getCountUnassigned()
    {
        try {
            return (int)static::raw('SELECT COUNT(*) as count FROM `@THIS` WHERE location_id IS NULL')->first()->get('count');
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
    public static function removeItem($id, $api = false)
    {
        try {
            $user = UserModel::getAuthUser();
            if ((!$user) && (!$api)) {
                throw new \Exception('Invalid user');
            }

            $row = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
            if (!$row) {
                throw new \Exception('Invalid item: ' . $id);
            }

            if (is_string($row->get('photo'))) {
                $oldThumbFile = public_path('/img/' . $row->get('photo'));

                if (file_exists($oldThumbFile)) {
                    unlink($oldThumbFile);
                }

                $oldOrigFile = public_path('/img/' . str_replace('_thumb', '', $row->get('photo')));

                if (file_exists($oldOrigFile)) {
                    unlink($oldOrigFile);
                }
            }

            static::raw('DELETE FROM `@THIS` WHERE id = ?', [$row->get('id')]);

            if (!$api) {
                LogModel::addLog($user->get('id'), 'inventory', 'remove_inventory_item', $row->get('name'), url('/inventory'));
                TextBlockModule::removedInventoryItem($row->get('name'));
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $group_ident
     * @return bool
     * @throws \Exception
     */
    public static function isGroupInUse($group_ident)
    {
        try {
            $row = static::raw('SELECT COUNT(*) AS `count` FROM `@THIS` WHERE group_ident = ?', [$group_ident])->first();
            if (!$row) {
                return false;
            }

            return $row->get('count') > 0;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $old_token
     * @param $new_token
     * @return void
     * @throws \Exception
     */
    public static function renameGroupToken($old_token, $new_token)
    {
        try {
            static::raw('UPDATE `@THIS` SET group_ident = ? WHERE group_ident = ?', [$new_token, $old_token]);
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
            $item = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
            if (!$item) {
                throw new \Exception('Invalid item: ' . $id);
            }

            $options = new QROptions();
            $options->invertMatrix = true;

            $oqr = new QRCode($options);
			return $oqr->render(url('/inventory?expand=' . $item->get('id') . '#anchor-item-' . $item->get('id')));
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $items
     * @return string
     * @throws \Exception
     */
    public static function exportItemsAsJson($items)
    {
        try {
            $pretty_items = [];

            foreach ($items as $item) {
                $pretty_items[$item[3]][] = [
                    'id' => $item[0],
                    'name' => $item[1],
                    'description' => $item[2],
                    'group' => $item[3],
                    'amount' => $item[4],
                    'location' => $item[5],
                    'photo' => $item[6],
                    'created' => $item[7],
                    'updated' => $item[8]
                ];
            }

            $data = [
                'meta' => [
                    'workspace' => app('workspace'),
                    'url' => url('/inventory'),
                    'exported' => date('Y-m-d H:i:s')
                ],

                'items' => $pretty_items
            ];

            $file_name = 'inventory_export_' . md5(random_bytes(55) . date('Y-m-d H:i:s')) . '.json';
            file_put_contents(public_path() . '/exports/' . $file_name, json_encode($data));

            return $file_name;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $items
     * @return string
     * @throws \Exception
     */
    public static function exportItemsAsCsv($items)
    {
        try {
            $data = "id,name,description,group,amount,location,photo,created,updated;" . PHP_EOL;

            foreach ($items as $item) {
                $data .= "\"" . $item[0] . "\",\"" . $item[1] . "\",\"" . $item[2] . "\",\"" . $item[3] . "\",\"" . $item[4] . "\",\"" . $item[5] . "\",\"" . $item[6] . "\",\"" . $item[7] . "\",\"" . $item[8] . "\"," . PHP_EOL;
            }

            $file_name = 'inventory_export_' . md5(random_bytes(55) . date('Y-m-d H:i:s')) . '.csv';
            file_put_contents(public_path() . '/exports/' . $file_name, $data);

            return $file_name;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Imports inventory items from an uploaded CSV file (accepted
     * columns: id, name, description, group, tags, amount, location -
     * any subset, matched case-insensitively; unknown columns are
     * ignored). Rows are matched to an existing item by 'id', when
     * present and valid, and updated - a blank cell on an otherwise
     * matched row leaves that field unchanged rather than clearing it.
     * Rows without a matching id are created instead, provided they
     * have a 'name' and a 'group' that resolves to a configured
     * inventory group (matched by token or by label). 'location' is
     * matched by name against an existing location when possible.
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

                $cell = function ($key) use ($data) {
                    $value = trim($data[$key] ?? '');
                    return ($value !== '') ? $value : null;
                };

                $name = $cell('name');
                $description = $cell('description');
                $tags = $cell('tags');
                $amount_raw = $cell('amount');
                $amount = (($amount_raw !== null) && (is_numeric($amount_raw))) ? (int)$amount_raw : null;

                $group_input = $cell('group');
                $group_token = null;
                if ($group_input !== null) {
                    if (InvGroupModel::isValidGroupToken($group_input)) {
                        $group_token = $group_input;
                    } else {
                        $group_row = InvGroupModel::raw('SELECT * FROM `@THIS` WHERE label = ?', [$group_input])->first();
                        if ($group_row) {
                            $group_token = $group_row->get('token');
                        }
                    }
                }

                $location_label = $cell('location');
                $location_id = null;
                if ($location_label !== null) {
                    $location_row = LocationsModel::raw('SELECT * FROM `@THIS` WHERE name = ?', [$location_label])->first();
                    if ($location_row) {
                        $location_id = $location_row->get('id');
                    }
                }

                $item_id_raw = $cell('id');
                $item_id = (($item_id_raw !== null) && (is_numeric($item_id_raw)) && ((int)$item_id_raw > 0)) ? (int)$item_id_raw : null;
                $existing = ($item_id) ? static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$item_id])->first() : null;

                if ($existing) {
                    static::editItem(
                        $existing->get('id'),
                        $name ?? $existing->get('name'),
                        $description ?? $existing->get('description'),
                        $tags ?? $existing->get('tags'),
                        ($location_id) ? $location_label : $existing->get('location'),
                        $amount ?? $existing->get('amount'),
                        $group_token ?? $existing->get('group_ident'),
                        null,
                        false,
                        ($location_id) ? $location_id : null
                    );

                    $updated++;
                } else {
                    if ($name === null) {
                        $errors[] = 'Row ' . $row_num . ': a name is required to create a new item';
                        continue;
                    }

                    if ($group_token === null) {
                        $errors[] = 'Row ' . $row_num . ': "' . htmlspecialchars($group_input ?? '', ENT_QUOTES) . '" is not a known inventory group, so this item could not be created';
                        continue;
                    }

                    static::addItem($name, $description ?? '', $tags ?? '', $location_label ?? '', $amount ?? 0, $group_token, null, false, ($location_id) ? $location_id : 'unassigned');

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
     * @param $items
     * @return string
     * @throws \Exception
     */
    public static function exportItemsAsPdf($items)
    {
        try {
            $file_name = 'inventory_export_' . md5(random_bytes(55) . date('Y-m-d H:i:s')) . '.pdf';

            $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor(env('APP_NAME'));
            $pdf->SetTitle(__('app.inventory') . ' | ' . __('app.export'));

            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
            $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

            $pdf->AddPage();
            $pdf->SetFont('helvetica', '', 12);

            $html = '
                <table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">
                    <thead>
                        <tr style="background-color: rgb(150, 150, 150); font-weight: bold; text-align: left;">
                            <td>ID</td>
                            <td>' . __('app.name') . '</td>
                            <td>' . __('app.description') . '</td>
                            <td>' . __('app.group') . '</td>
                            <td>' . __('app.amount') . '</td>
                            <td>' . __('app.location') . '</td>
                            <td>' . __('app.photo') . '</td>
                            <td>' . __('app.created_at') . '</td>
                            <td>' . __('app.updated_at') . '</td>
                        </tr>
                    </thead>
                    <tbody>
            ';

            foreach ($items as $item) {
                $html .= '
                    <tr>
                        <td>#' . $item[0] . '</td>
                        <td>' . $item[1] . '</td>
                        <td>' . $item[2] . '</td>
                        <td>' . $item[3] . '</td>
                        <td>' . $item[4] . '</td>
                        <td>' . $item[5] . '</td>
                        <td>' . $item[6] . '</td>
                        <td>' . $item[7] . '</td>
                        <td>' . $item[8] . '</td>
                    </tr>
                ';
            }

            $html .= '
                </tbody>
                </table>
            ';

            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->Output(public_path() . '/exports/' . $file_name, 'F');

            return $file_name;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $items
     * @param $format
     * @return string
     * @throws \Exception
     */
    public static function exportItems($items, $format)
    {
        try {
            $file = '';

            $exports = static::exports();

            if (isset($exports[$format])) {
                $file = static::{$exports[$format]['method']}($items);
            } else {
                throw new \Exception('Unsupported format: ' . $format);
            }

            return $file;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return array
     */
    public static function exports()
    {
        return static::$supported_exports;
    }
}