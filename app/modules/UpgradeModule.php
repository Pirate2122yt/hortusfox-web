<?php

/**
 * Class UpgradeModule
 * 
 * Perform migration upgrades
 */
class UpgradeModule {
    /**
     * Adds a parent-plant reference, separate from the existing
     * clone_origin/clone_num duplication feature: parent_plant just
     * links an independently-added plant to the plant it was
     * propagated from, with no field copying and no effect on the
     * "(2)" clone-suffix naming.
     *
     * @return void
     */
    private static function upgradeTo5dot43()
    {
        PlantsModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS parent_plant INT NULL');
    }

    /**
     * Adds optional two-factor login (TOTP): a secret, an enabled flag,
     * and a JSON blob of hashed one-time recovery codes, all on the
     * user's own row.
     *
     * @return void
     */
    private static function upgradeTo5dot42()
    {
        UserModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS totp_secret VARCHAR(64) NULL');
        UserModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS totp_enabled BOOLEAN NOT NULL DEFAULT 0');
        UserModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS totp_recovery_codes TEXT NULL');
    }

    /**
     * Adds a recycle bin for plants: removing a plant now just sets
     * deleted_at instead of erasing it outright, so it can be restored
     * or, once someone empties the bin, permanently purged.
     *
     * @return void
     */
    private static function upgradeTo5dot41()
    {
        PlantsModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL');
    }

    /**
     * Adds the Changelog feature: a new ChangelogModel table for a
     * short, admin-curated history of notable changes (separate from
     * FeatureRequestModel so an entry here has no requester/votes and
     * isn't part of the community board), seeded with the significant
     * changes shipped so far so the page isn't empty on first visit.
     *
     * @return void
     */
    private static function upgradeTo5dot40()
    {
        ChangelogModel::raw('CREATE TABLE IF NOT EXISTS ChangelogModel (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            entry_date DATE NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        $already_seeded = ChangelogModel::raw('SELECT COUNT(*) as count FROM `@THIS`')->first()->get('count') > 0;
        if ($already_seeded) {
            return;
        }

        $entries = [
            ['2026-09-06', 'Month-view calendar, and Plant.id as a fallback ID provider', 'The calendar was rebuilt as a proper month view, and Plant.id was added as a fallback plant identification provider.'],
            ['2026-09-07', 'Plant journal', 'Added a plant journal for photos, tags and notes on log entries, including a settable entry date for backdating old events.'],
            ['2026-09-07', 'Journal photo syncing and a rewritten Location Log', 'Journal photos now sync automatically into the gallery, care dates get marked straight from matching journal entries, uploaded photos have their metadata stripped, and the Location Log was rewritten.'],
            ['2026-09-08', 'Feature Request board', 'Added a Feature Request board where anyone can suggest new features and upvote the ones they want most.'],
            ['2026-09-08', 'Places', 'Added Places to group Locations together (e.g. a house containing rooms).'],
            ['2026-09-08', 'Per-Location inventory pages', 'Inventory now has its own page per Location.'],
            ['2026-09-08', 'Public plant catalogue', 'Added a shareable, no-login public catalogue of your plants, with visitor comments, a rate-limited and CAPTCHA-protected public plant identifier, and an admin kill switch to turn it off entirely.'],
            ['2026-09-08', 'iCal and RSS feeds', 'Added an iCal (.ics) calendar feed and an RSS activity feed for tools like Homarr and other feed readers.'],
            ['2026-09-08', 'Chat and update-check polish', 'Added a toggle to hide system messages in chat, and the app now checks your own GitHub repo (instead of upstream) for available updates.'],
            ['2026-09-09', 'All-plants page', 'Added an all-plants page, and the dashboard now shows Places before Locations.'],
            ['2026-09-09', 'Photos for Places', 'You can now upload a photo for a Place (house), mirroring Location photos.'],
            ['2026-09-09', 'Default weather location', 'Added a default weather location with admin-set coordinates.'],
            ['2026-09-09', 'Color schemes (Appearance)', 'Added selectable color schemes under Appearance, with several built-in themes to choose from.'],
            ['2026-09-10', 'More color schemes', 'Added three more color schemes: Ember, Abyss, and Amber.'],
            ['2026-09-10', 'Care interval reminders', 'Added per-plant, per-action care interval reminders.'],
            ['2026-09-10', 'Plant health-state history', "Each plant's health-state changes are now tracked and shown as a timeline."],
            ['2026-09-10', 'CSV import/export', 'Added CSV import/export for plants, and CSV import for inventory.'],
            ['2026-09-10', 'Web push notifications', 'Added web push notifications - subscribe/unsubscribe and a test push - wired into task, calendar and chat reminders.'],
            ['2026-09-10', 'Chat message editing and deletion', "Admins can now delete chat messages, and users can edit their own."],
            ['2026-09-10', 'Weather based on Places', 'Weather is now based on a Place rather than an individual Location.'],
            ['2026-09-10', 'Location-based notification filtering', 'Added location-based filtering for task and plant-care push notifications.'],
            ['2026-09-10', 'Per-location calendar events', 'Added per-location calendar events, and scoped the homepage to them.'],
            ['2026-09-10', 'Reorganized Settings', 'Settings were reorganized into labeled sections for easier navigation.'],
            ['2026-09-10', 'Comment notification emails', "Visitors commenting on a shared plant now emails the admins, and admins can choose exactly which admin accounts receive that notification."],
            ['2026-09-10', 'Feature request notification emails', 'New feature request submissions can now email a chosen admin via SMTP.'],
            ['2026-09-10', 'Plant Wishlist', 'Added the plant Wishlist feature: a per-user wishlist, a per-house wishlist, and an overall wishlist, with a shareable public gift-registry link.'],
            ['2026-09-11', 'Separated the three Wishlist views', "The House Wishlist view now has you pick a specific house first, so Everyone's, House, and My wishlist are genuinely separate views."],
            ['2026-09-11', 'This changelog', 'Added this changelog, linked from the Feature Request board.']
        ];

        foreach ($entries as $entry) {
            ChangelogModel::raw('INSERT INTO `@THIS` (title, description, entry_date) VALUES(?, ?, ?)', [
                $entry[1], $entry[2], $entry[0]
            ]);
        }
    }

    /**
     * WishlistModel.price started out as DECIMAL(10, 2), which only
     * allows 8 digits before the decimal point (max 99,999,999.99) -
     * plenty in theory, but MySQL rejects the insert outright rather
     * than truncating once a value doesn't fit, which surfaced as a raw
     * SQLSTATE error to the user instead of a friendly one. Widened
     * here rather than relying on validation alone, since the column
     * itself was the more surprising part of that failure.
     *
     * @return void
     */
    private static function upgradeTo5dot39()
    {
        WishlistModel::raw('ALTER TABLE `@THIS` MODIFY COLUMN price DECIMAL(12, 2) NULL');
    }

    /**
     * Adds the plant Wishlist feature: a new WishlistModel table for
     * per-user wishlist entries, an admin-wide enable toggle (mirroring
     * tasks_enable/calendar_enable), and a per-user opt-in public
     * share link (mirroring the password-reset token pattern already
     * used on this table) so a user can hand out a gift-registry-style
     * link to their own wishlist without anyone logging in.
     *
     * @return void
     */
    private static function upgradeTo5dot38()
    {
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS wishlist_enable BOOLEAN NOT NULL DEFAULT 1');

        UserModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS wishlist_share_enable BOOLEAN NOT NULL DEFAULT 0');
        UserModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS wishlist_share_token VARCHAR(64) NULL');

        WishlistModel::raw('CREATE TABLE IF NOT EXISTS WishlistModel (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            species VARCHAR(512) NULL,
            cultivar VARCHAR(512) NULL,
            notes TEXT NULL,
            priority VARCHAR(32) NOT NULL DEFAULT \'would_like\',
            photo VARCHAR(255) NULL,
            location INT NULL,
            source_url VARCHAR(1024) NULL,
            price DECIMAL(12, 2) NULL,
            best_time_note VARCHAR(512) NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');
    }

    /**
     * Lets the admin narrow the "email admins about new public comments"
     * notification down to specific admin accounts instead of always
     * broadcasting to every admin. Stored as a comma-separated list of
     * UserModel ids, the same shape already used elsewhere in the app for
     * a handful of selected ids (see e.g. PlantsController::apply_plants);
     * NULL/empty keeps the original "notify every admin" behavior.
     *
     * @return void
     */
    private static function upgradeTo5dot37()
    {
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS public_comment_notify_admin_ids TEXT NULL');
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot36()
    {
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS public_comment_notify_admins BOOLEAN NOT NULL DEFAULT 1');
    }

    /**
     * Calendar events can now optionally be tied to a Location, the same
     * way Tasks are tied to one through a linked Plant. Used to filter
     * calendar reminder pushes through the preferred-Locations filter and
     * to scope the homepage's task/plant summary to those Locations too.
     *
     * @return void
     */
    private static function upgradeTo5dot35()
    {
        CalendarModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS location INT NULL');
    }

    /**
     * Adds push notifications for plant care reminders (email-only until
     * now) and a per-user preferred-Locations filter that narrows down
     * which Locations' push notifications (Tasks and Plant care) a user
     * actually wants to receive.
     *
     * @return void
     */
    private static function upgradeTo5dot34()
    {
        UserModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS push_plant_care BOOLEAN NOT NULL DEFAULT 1');

        UserPreferredLocationModel::raw('CREATE TABLE IF NOT EXISTS UserPreferredLocationModel (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user INT NOT NULL,
            location INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');
    }

    /**
     * Weather is now configured per-Place instead of per-Location, since a
     * Place (e.g. a house) is the more natural unit for "where is this" than
     * an individual Location (a room) inside it. Moves the coordinate
     * columns from LocationsModel to PlacesModel, folding any existing
     * per-Location coordinates up to their Place (first Location with
     * coordinates wins if a Place has several), and carries each user's
     * previously-chosen weather Location forward as that Location's Place.
     *
     * @return void
     */
    private static function upgradeTo5dot33()
    {
        PlacesModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS weather_latitude DECIMAL(10, 8) NULL');
        PlacesModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS weather_longitude DECIMAL(11, 8) NULL');

        UserModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS weather_place INT NULL');

        $locations_with_weather = LocationsModel::raw('SELECT * FROM `LocationsModel` WHERE weather_latitude IS NOT NULL AND weather_longitude IS NOT NULL');
        foreach ($locations_with_weather as $location) {
            if (!$location->get('place')) {
                continue;
            }

            $place = PlacesModel::getById($location->get('place'));
            if (($place) && ($place->get('weather_latitude') === null) && ($place->get('weather_longitude') === null)) {
                PlacesModel::raw('UPDATE `@THIS` SET weather_latitude = ?, weather_longitude = ? WHERE id = ?', [
                    $location->get('weather_latitude'), $location->get('weather_longitude'), $place->get('id')
                ]);
            }
        }

        $users_with_weather_location = UserModel::raw('SELECT * FROM `@THIS` WHERE weather_location IS NOT NULL');
        foreach ($users_with_weather_location as $weather_user) {
            $location = LocationsModel::getLocationById($weather_user->get('weather_location'));
            if (($location) && ($location->get('place'))) {
                UserModel::raw('UPDATE `@THIS` SET weather_place = ? WHERE id = ?', [$location->get('place'), $weather_user->get('id')]);
            }
        }

        UserModel::raw('ALTER TABLE `@THIS` DROP COLUMN IF EXISTS weather_location');

        LocationsModel::raw('ALTER TABLE `@THIS` DROP COLUMN IF EXISTS weather_latitude');
        LocationsModel::raw('ALTER TABLE `@THIS` DROP COLUMN IF EXISTS weather_longitude');
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot32()
    {
        UserModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS push_tasks_overdue BOOLEAN NOT NULL DEFAULT 1');
        UserModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS push_tasks_tomorrow BOOLEAN NOT NULL DEFAULT 1');
        UserModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS push_tasks_recurring BOOLEAN NOT NULL DEFAULT 1');
        UserModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS push_calendar_reminder BOOLEAN NOT NULL DEFAULT 1');
        UserModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS push_chat_message BOOLEAN NOT NULL DEFAULT 1');
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot31()
    {
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS push_enable BOOLEAN NOT NULL DEFAULT 0');
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS vapid_subject VARCHAR(255) NULL');
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS vapid_public_key VARCHAR(255) NULL');
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS vapid_private_key VARCHAR(255) NULL');

        PushSubscriptionModel::raw('CREATE TABLE IF NOT EXISTS PushSubscriptionModel (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user INT NOT NULL,
            endpoint VARCHAR(1024) NOT NULL,
            p256dh VARCHAR(255) NOT NULL,
            auth_token VARCHAR(255) NOT NULL,
            user_agent VARCHAR(512) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot30()
    {
        PlantHealthLogModel::raw('CREATE TABLE IF NOT EXISTS PlantHealthLogModel (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            plant INT NOT NULL,
            health_state VARCHAR(512) NOT NULL,
            recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        //Seed a starting data point for every plant that predates this feature, so their timeline isn't empty
        PlantHealthLogModel::raw('INSERT INTO `@THIS` (plant, health_state, recorded_at) SELECT id, health_state, created_at FROM `PlantsModel` WHERE id NOT IN (SELECT DISTINCT plant FROM `@THIS`)');
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot29()
    {
        PlantsModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS water_interval_days INT NULL');
        PlantsModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS fertilise_interval_days INT NULL');
        PlantsModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS repot_interval_days INT NULL');

        UserModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS notify_plant_care BOOLEAN NOT NULL DEFAULT 1');

        PlantCareInformerModel::raw('CREATE TABLE IF NOT EXISTS PlantCareInformerModel (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            plant INT NOT NULL,
            user INT NOT NULL,
            action VARCHAR(512) NOT NULL,
            notified_for DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot28()
    {
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS feature_request_notify_user INT NULL');
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot27()
    {
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS color_scheme VARCHAR(512) NULL');
        UserModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS color_scheme VARCHAR(512) NULL');
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot26()
    {
        LocationsModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS weather_latitude DECIMAL(10, 8) NULL');
        LocationsModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS weather_longitude DECIMAL(11, 8) NULL');
        UserModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS weather_location INT NULL');
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot25()
    {
        PlacesModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS icon VARCHAR(512) NULL');
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot24()
    {
        ChatMsgModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS display_name VARCHAR(100) NULL');
    }

    /**
     * @return void
     */
    private static function upgradeTo5dot23()
    {
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS public_plantid_enable BOOLEAN NOT NULL DEFAULT 0');
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS public_captcha_sitekey VARCHAR(512) NULL');
        AppModel::raw('ALTER TABLE `@THIS` ADD COLUMN IF NOT EXISTS public_captcha_secretkey VARCHAR(512) NULL');

        PublicIdentifyRequestModel::raw('CREATE TABLE IF NOT EXISTS PublicIdentifyRequestModel (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            ip_hash VARCHAR(64) NOT NULL,
            request_date DATE NOT NULL,
            request_count INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_ip_date (ip_hash, request_date)
        )');
    }

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
