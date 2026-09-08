<?php

/**
 * Class UpgradeModule
 * 
 * Perform migration upgrades
 */
class UpgradeModule {
    /**
     * @return void
     */
    private static function upgradeTo5dot22()
    {
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS public_catalog_enable BOOLEAN NOT NULL DEFAULT 1');
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot19()
    {
        PlantsModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS is_public BOOLEAN NOT NULL DEFAULT 0');

        PlantLogCommentModel::raw('CREATE TABLE IF NOT EXISTS PlantLogCommentModel (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            log_entry INT NOT NULL,
            author_name VARCHAR(255) NULL,
            comment TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot18()
    {
        InventoryModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS location_id INT NULL');
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot17()
    {
        PlacesModel::raw('CREATE TABLE IF NOT EXISTS PlacesModel (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(512) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        LocationsModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS place INT NULL');

        if (PlacesModel::getCount() == 0) {
            PlacesModel::addPlace('Home');
        }

        $default_place = PlacesModel::raw('SELECT * FROM `PlacesModel` ORDER BY id ASC LIMIT 1')->first();

        if ($default_place) {
            LocationsModel::raw('UPDATE `@THIS` SET place = ? WHERE place IS NULL', [$default_place->get('id')]);
        }
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot16()
    {
        FeatureRequestModel::raw('CREATE TABLE IF NOT EXISTS FeatureRequestModel (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            status VARCHAR(32) NOT NULL DEFAULT \'open\',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        FeatureRequestVoteModel::raw('CREATE TABLE IF NOT EXISTS FeatureRequestVoteModel (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            request_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot15()
    {
        LocationLogModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS title VARCHAR(255) NOT NULL DEFAULT \'\'');
        LocationLogModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS tags VARCHAR(512) NOT NULL DEFAULT \'\'');
        LocationLogModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS is_system TINYINT(1) NOT NULL DEFAULT 0');
        LocationLogModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS entry_date DATE NULL');
        LocationLogModel::raw('UPDATE `@THIS` SET is_system = 1, title = TRIM(SUBSTRING(content, 10)), content = \'\' WHERE content LIKE \'[System]%\' AND title = \'\'');
        LocationLogModel::raw('UPDATE `@THIS` SET title = content, content = \'\' WHERE title = \'\' AND is_system = 0 AND content <> \'\'');
        LocationLogModel::raw('UPDATE `@THIS` SET entry_date = DATE(created_at) WHERE entry_date IS NULL');

        LocationLogPhotoModel::raw('CREATE TABLE IF NOT EXISTS LocationLogPhotoModel (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            log_entry INT NOT NULL,
            thumb VARCHAR(255) NOT NULL,
            original VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        LocationLogPlantModel::raw('CREATE TABLE IF NOT EXISTS LocationLogPlantModel (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            log_entry INT NOT NULL,
            plant INT NOT NULL
        )');
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot13()
    {
        PlantLogModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS entry_date DATE NULL');
        PlantLogModel::raw('UPDATE `@THIS` SET entry_date = DATE(created_at) WHERE entry_date IS NULL');
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot12()
    {
        PlantLogModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS title VARCHAR(255) NOT NULL DEFAULT \'\'');
        PlantLogModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS is_system TINYINT(1) NOT NULL DEFAULT 0');
        PlantLogModel::raw('UPDATE `@THIS` SET is_system = 1, title = TRIM(SUBSTRING(content, 10)), content = \'\' WHERE content LIKE \'[System]%\' AND title = \'\'');
        PlantLogModel::raw('UPDATE `@THIS` SET title = content, content = \'\' WHERE title = \'\' AND is_system = 0 AND content <> \'\'');

        PlantLogPhotoModel::raw('CREATE TABLE IF NOT EXISTS PlantLogPhotoModel (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            log_entry INT NOT NULL,
            thumb VARCHAR(255) NOT NULL,
            original VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        PlantLogPhotoModel::raw('INSERT INTO PlantLogPhotoModel (log_entry, thumb, original) SELECT id, photo_thumb, photo_original FROM PlantLogModel WHERE photo_thumb IS NOT NULL AND photo_original IS NOT NULL');
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot11()
    {
        PlantLogModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS tags VARCHAR(512) NOT NULL DEFAULT \'\'');
        PlantLogModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS photo_thumb VARCHAR(255) NULL');
        PlantLogModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS photo_original VARCHAR(255) NULL');
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot10()
    {
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS plantrec_provider VARCHAR(32) NOT NULL DEFAULT \'plantnet\'');
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS plantrec_apikey_plantid VARCHAR(512) NULL');
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot9()
    {
        UserModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS remember_location_sorting BOOLEAN NOT NULL DEFAULT 0');
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot8()
    {
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot7()
    {
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot6()
    {
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot5()
    {
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot4()
    {
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot3()
    {
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS smtp_enable_auth BOOLEAN NOT NULL DEFAULT 1');

        PlantsModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS lifespan VARCHAR(512) NULL');

        $plants = PlantsModel::raw('SELECT * FROM `@THIS`');
        foreach ($plants as $plant) {
            if ($plant->get('annual')) {
                PlantsModel::raw('UPDATE `@THIS` SET lifespan = ? WHERE id = ?', ['lifespan_annual', $plant->get('id')]);
            } else if ($plant->get('perennial')) {
                PlantsModel::raw('UPDATE `@THIS` SET lifespan = ? WHERE id = ?', ['lifespan_perennial', $plant->get('id')]);
            }
        }

        PlantsModel::raw('ALTER TABLE `@THIS` DROP COLUMN IF EXISTS annual');
        PlantsModel::raw('ALTER TABLE `@THIS` DROP COLUMN IF EXISTS perennial');

        CustBulkCmdModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS datatype VARCHAR(512) NOT NULL DEFAULT \'datetime\'');

        TasksModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS recurring_scope VARCHAR(512) NOT NULL DEFAULT \'hours\'');
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot2()
    {
        LocationsModel::raw('ALTER TABLE `@THIS` MODIFY COLUMN icon VARCHAR(512) NULL');
        PlantsModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS clone_origin INT NULL');
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot1()
    {
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot0()
    {
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS quick_add BOOLEAN NOT NULL DEFAULT 0');
        PlantsModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS hardy BOOLEAN NULL');
        PlantDefAttrModel::raw('INSERT INTO `@THIS` (name, active) VALUES(?, ?)', ['hardy', true]);
    }

    /**
     * @return void
     */
    private static function upgradeTo4dot9()
    {
    }

    /**
     * @return void
     */
    private static function upgradeTo4dot8()
    {
    }

    /**
     * @return void
     */
    private static function upgradeTo4dot7()
    {
    }

    /**
     * @return void
     */
    private static function upgradeTo4dot6()
    {
    }

    /**
     * @return void
     */
    private static function upgradeTo4dot5()
    {
        TasksModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS recurring_time INT NULL');
        TasksModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');

        UserModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS notify_tasks_recurring BOOLEAN NOT NULL DEFAULT 1');

        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS plantrec_quickscan BOOLEAN NOT NULL DEFAULT 0');
    }

    /**
     * @return void
     */
    private static function upgradeTo4dot4()
    {
    }

    /**
     * @return void
     */
    private static function upgradeTo4dot3()
    {
        LocationsModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS notes TEXT NULL');
    }

    /**
     * @return void
     */
    private static function upgradeTo4dot2()
    {
    }

    /**
     * @return void
     */
    private static function upgradeTo4dot1()
    {
    }

    /**
     * @return void
     */
    private static function upgradeTo4dot0()
    {
        PlantsModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS last_photo_date DATETIME NULL');

        InventoryModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS tags VARCHAR(512) NULL');
    }

    /**
     * @return void
     */
    private static function upgradeTo3dot9()
    {
    }

    /**
     * @return void
     */
    private static function upgradeTo3dot8()
    {
    }

    /**
     * @return void
     */
    private static function upgradeTo3dot7()
    {
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS tasks_enable BOOLEAN NOT NULL DEFAULT 1');
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS inventory_enable BOOLEAN NOT NULL DEFAULT 1');
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS calendar_enable BOOLEAN NOT NULL DEFAULT 1');
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS custom_head_code TEXT NULL DEFAULT \'\'');
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS plantrec_enable BOOLEAN NOT NULL DEFAULT 0');
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS plantrec_apikey VARCHAR(512) NULL');
    }

    /**
     * @return void
     */
    private static function upgradeTo3dot6()
    {
    }

    /**
     * @return void
     */
    private static function upgradeTo3dot5()
    {
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS auth_proxy_enable BOOLEAN NOT NULL DEFAULT 0');
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS auth_proxy_header_email VARCHAR(512) NULL');
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS auth_proxy_header_username VARCHAR(512) NULL');
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS auth_proxy_sign_up BOOLEAN NOT NULL DEFAULT 0');
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS auth_proxy_whitelist TEXT NULL');
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS auth_proxy_hide_logout BOOLEAN NOT NULL DEFAULT 0');
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS custom_media_share_host VARCHAR(1024) NULL');
    }

    /**
     * @return void
     */
    private static function upgradeTo3dot4()
    {
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS auto_backup BOOLEAN NOT NULL DEFAULT 0');
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS backup_path VARCHAR(1024) NULL');
    }

    /**
     * @return void
     */
    private static function upgradeTo3dot3()
    {
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS mail_rp_address VARCHAR(512) NULL');
    }

    /**
     * @return void
     */
    private static function upgradeTo3dot2()
    {
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS allow_custom_attributes BOOLEAN NOT NULL DEFAULT 0');

        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS system_message_plant_log BOOLEAN NOT NULL DEFAULT 1');

        ChatMsgModel::raw('ALTER TABLE `@THIS` RENAME COLUMN IF EXISTS system TO sysmsg');
    }

    /**
     * @return void
     */
    private static function upgradeTo3dot1()
    {
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS timezone VARCHAR(512) NULL');

        PlantsModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS annual BOOLEAN NULL');

        UserModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS show_plant_id BOOLEAN NOT NULL DEFAULT 0');
    }

    /**
     * @return void
     */
    private static function upgradeTo3dot0()
    {
        PlantsModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS clone_num INT NULL');

        InventoryModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS location VARCHAR(512) NULL');

        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS owm_enable BOOLEAN NOT NULL DEFAULT 0');
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS owm_api_key VARCHAR(512) NULL');
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS owm_latitude DECIMAL(10, 8) NULL');
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS owm_longitude DECIMAL(11, 8) NULL');
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS owm_unittype VARCHAR(512) NOT NULL DEFAULT \'default\'');
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS owm_cache INT NOT NULL DEFAULT 300');
    }

    /**
     * @return void
     */
    private static function upgradeTo2dot5()
    {
        PlantsModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS last_fertilised DATETIME NULL');

        UserModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS notify_calendar_reminder BOOLEAN NOT NULL DEFAULT 1');
        UserModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS show_calendar_view BOOLEAN NOT NULL DEFAULT 1');
    }

    /**
     * @return void
     */
    private static function upgradeTo2dot4()
    {
        UserModel::raw('ALTER TABLE `@THIS` DROP COLUMN IF EXISTS session');
        UserModel::raw('ALTER TABLE `@THIS` DROP COLUMN IF EXISTS status');

        PlantsModel::raw('ALTER TABLE `@THIS` MODIFY COLUMN perennial BOOLEAN NULL');
        PlantsModel::raw('ALTER TABLE `@THIS` MODIFY COLUMN humidity INT NULL');
        PlantsModel::raw('ALTER TABLE `@THIS` MODIFY COLUMN light_level VARCHAR(512) NULL');
    }

    /**
     * @return void
     */
    private static function ensureTableMigration()
    {
        $plantsTable = PlantsModel::raw('SHOW TABLES LIKE \'@THIS\';')->first();
        if ($plantsTable === null) {
            try {
                ApiModel::raw('RENAME TABLE `apitable` TO `@THIS`');
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
    
            try {
                CalendarClassModel::raw('RENAME TABLE `calendarclasses` TO `@THIS`');
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
    
            try {
                CalendarInformerModel::raw('RENAME TABLE `calendarinformer` TO `@THIS`');
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
    
            try {
                CalendarModel::raw('RENAME TABLE `calendar` TO `@THIS`');
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
    
            try {
                ChatMsgModel::raw('RENAME TABLE `chatmsg` TO `@THIS`');
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
    
            try {
                ChatViewModel::raw('RENAME TABLE `chatview` TO `@THIS`');
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
    
            try {
                CustAttrSchemaModel::raw('RENAME TABLE `custattrschema` TO `@THIS`');
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
    
            try {
                CustBulkCmdModel::raw('RENAME TABLE `custbulkcmd` TO `@THIS`');
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
    
            try {
                CustPlantAttrModel::raw('RENAME TABLE `custplantattr` TO `@THIS`');
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
    
            try {
                InventoryModel::raw('RENAME TABLE `inventory` TO `@THIS`');
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
    
            try {
                InvGroupModel::raw('RENAME TABLE `invgroup` TO `@THIS`');
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
    
            try {
                LocationLogModel::raw('RENAME TABLE `locationlog` TO `@THIS`');
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
    
            try {
                LocationsModel::raw('RENAME TABLE `locations` TO `@THIS`');
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
    
            try {
                LogModel::raw('RENAME TABLE `log` TO `@THIS`');
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
    
            try {
                PlantDefAttrModel::raw('RENAME TABLE `plantdefattr` TO `@THIS`');
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
    
            try {
                PlantLogModel::raw('RENAME TABLE `plantlog` TO `@THIS`');
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
    
            try {
                PlantPhotoModel::raw('RENAME TABLE `plantphotos` TO `@THIS`');
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
    
            try {
                PlantsModel::raw('RENAME TABLE `plants` TO `@THIS`');
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
    
            try {
                ShareLogModel::raw('RENAME TABLE `sharelog` TO `@THIS`');
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
    
            try {
                TaskInformerModel::raw('RENAME TABLE `taskinformer` TO `@THIS`');
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
    
            try {
                TasksModel::raw('RENAME TABLE `tasks` TO `@THIS`');
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
    
            try {
                UserModel::raw('RENAME TABLE `users` TO `@THIS`');
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
        }
    }

    /**
     * @param $version
     * @return bool
     * @throws \Exception
     */
    public static function upgrade($version)
    {
        try {
            $method = 'upgradeTo' . str_replace('.', 'dot', $version);
                    
            if (method_exists(self::class, $method)) {
                if (version_compare($version, '4.0', '>')) {
                    static::ensureTableMigration();
                }

                static::$method();

                return true;
            }

            return false;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
