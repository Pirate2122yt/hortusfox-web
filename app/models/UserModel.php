<?php

/**
 * Class UserModel
 * 
 * Manages user accounts
 */ 
class UserModel extends \Asatru\Database\Model {
    /**
     * @return mixed
     */
    public static function getAuthUser()
    {
        try {
            $session = SessionModel::findSession(session_id());
            if (!$session) {
                return null;
            }

            $data = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$session->get('userId')])->first();
            if (!$data) {
                return null;
            }

            return $data;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public static function performProxyAuth()
    {
        try {
            if (!app('auth_proxy_enable')) {
                throw new \Exception('Feature is currently not active');
            }

            $whitelist = app('auth_proxy_whitelist');
            if ((is_string($whitelist)) && (strlen($whitelist) > 0)) {
                $accepted = false;
                $remote_addr = $_SERVER['REMOTE_ADDR'];
                $whitelist = explode(PHP_EOL, $whitelist);

                foreach ($whitelist as $address) {
                    if ($address === $remote_addr) {
                        $accepted = true;
                        break;
                    }
                }

                if (!$accepted) {
                    throw new \Exception('Unauthorized remote address: ' . $remote_addr);
                }
            }

            $header_email = $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', app('auth_proxy_header_email')))] ?? null;
            if ((!is_string($header_email)) || (strlen($header_email) == 0)) {
                throw new \Exception('Invalid E-Mail header value provided: ' . $header_email);
            }

            $header_username = $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', app('auth_proxy_header_username')))] ?? null;
            if ((!is_string($header_username)) || (strlen($header_username) == 0)) {
                throw new \Exception('Invalid username header value provided: ' . $header_username);
            }

            $authuser = static::raw('SELECT * FROM `@THIS` WHERE email = ?', [$header_email])->first();
            if (!$authuser) {
                if (app('auth_proxy_sign_up')) {
                    static::createUser($header_username, $header_email, false);

                    $authuser = static::raw('SELECT * FROM `@THIS` WHERE email = ?', [$header_email])->first();
                } else {
                    throw new \Exception('User not found: ' . $header_email);
                }
            }

            if (!$header_username !== $authuser->get('name')) {
                static::raw('UPDATE `@THIS` SET name = ? WHERE id = ?', [$header_username, $authuser->get('id')]);
            }
            
            SessionModel::loginSession($authuser->get('id'), session_id());
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $email
     * @param $password
     * @return bool True if a second factor (TOTP) is still needed before
     *              the session is actually logged in, false if login is
     *              already complete.
     * @throws \Exception
     */
    public static function login($email, $password)
    {
        try {
            $data = static::raw('SELECT * FROM `@THIS` WHERE email = ?', [$email])->first();
            if (!$data) {
                throw new \Exception(__('app.user_not_found', ['email' => $email]));
            }

            if (!password_verify($password, $data->get('password'))) {
                throw new \Exception(__('app.password_mismatch'));
            }

            if ($data->get('totp_enabled')) {
                // Password is correct, but two-factor is on - hold the
                // user ID in a plain session var (not SessionModel, so
                // getAuthUser()/auth() still see them as logged out)
                // until verifyTotpLogin() below actually completes the
                // login.
                $_SESSION['pending_totp_user'] = $data->get('id');

                return true;
            }

            SessionModel::loginSession($data->get('id'), session_id());

            return false;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Generates and stores a fresh TOTP secret for the signed-in user,
     * without enabling two-factor yet - it only takes effect once
     * confirmTotpSetup() below verifies the user actually scanned it
     * correctly.
     *
     * @return array{secret: string, uri: string}
     * @throws \Exception
     */
    public static function beginTotpSetup()
    {
        try {
            $user = static::getAuthUser();
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            $secret = TOTPModule::generateSecret();

            static::raw('UPDATE `@THIS` SET totp_secret = ?, totp_enabled = 0, totp_recovery_codes = NULL WHERE id = ?', [$secret, $user->get('id')]);

            return [
                'secret' => $secret,
                'uri' => TOTPModule::getProvisioningUri($secret, $user->get('email'))
            ];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Confirms a pending TOTP setup with a code from the authenticator
     * app, turns two-factor on, and issues a fresh set of recovery
     * codes (returned in plaintext for one-time display - only their
     * hashes are stored).
     *
     * @param $code
     * @return array
     * @throws \Exception
     */
    public static function confirmTotpSetup($code)
    {
        try {
            $user = static::getAuthUser();
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            $secret = $user->get('totp_secret');
            if (!$secret) {
                throw new \Exception(__('app.totp_setup_not_started'));
            }

            if (!TOTPModule::verifyCode($secret, $code)) {
                throw new \Exception(__('app.totp_code_invalid'));
            }

            $recovery_codes = TOTPModule::generateRecoveryCodes();
            $hashed = array_map(function ($recovery_code) {
                return password_hash($recovery_code, PASSWORD_BCRYPT);
            }, $recovery_codes);

            static::raw('UPDATE `@THIS` SET totp_enabled = 1, totp_recovery_codes = ? WHERE id = ?', [json_encode($hashed), $user->get('id')]);

            return $recovery_codes;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Turns two-factor off after re-checking the current password, and
     * clears the stored secret and recovery codes.
     *
     * @param $password
     * @return void
     * @throws \Exception
     */
    public static function disableTotp($password)
    {
        try {
            $user = static::getAuthUser();
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            if (!password_verify($password, $user->get('password'))) {
                throw new \Exception(__('app.password_mismatch'));
            }

            static::raw('UPDATE `@THIS` SET totp_enabled = 0, totp_secret = NULL, totp_recovery_codes = NULL WHERE id = ?', [$user->get('id')]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Completes a login that was held pending a second factor by
     * login() above: verifies the code (a live TOTP code, or falls back
     * to a one-time recovery code) against the pending user and, only
     * on success, actually opens the session.
     *
     * @param $code
     * @return void
     * @throws \Exception
     */
    public static function verifyTotpLogin($code)
    {
        try {
            $userId = $_SESSION['pending_totp_user'] ?? null;
            if (!$userId) {
                throw new \Exception(__('app.totp_login_expired'));
            }

            $user = static::getUserById($userId);
            if (!$user) {
                throw new \Exception(__('app.totp_login_expired'));
            }

            $secret = $user->get('totp_secret');
            $verified = (($secret) && (TOTPModule::verifyCode($secret, $code)));

            if (!$verified) {
                $verified = static::consumeRecoveryCode($user, $code);
            }

            if (!$verified) {
                throw new \Exception(__('app.totp_code_invalid'));
            }

            unset($_SESSION['pending_totp_user']);

            SessionModel::loginSession($user->get('id'), session_id());
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Checks a code against the user's remaining hashed recovery codes
     * and, on a match, removes that one code so it can't be reused.
     *
     * @param $user
     * @param $code
     * @return bool
     */
    private static function consumeRecoveryCode($user, $code)
    {
        if ((!is_string($code)) || (strlen(trim($code)) === 0)) {
            return false;
        }

        $stored = $user->get('totp_recovery_codes');
        if (!$stored) {
            return false;
        }

        $hashes = json_decode($stored, true);
        if (!is_array($hashes)) {
            return false;
        }

        $code = trim($code);

        foreach ($hashes as $key => $hash) {
            if (password_verify($code, $hash)) {
                unset($hashes[$key]);

                static::raw('UPDATE `@THIS` SET totp_recovery_codes = ? WHERE id = ?', [json_encode(array_values($hashes)), $user->get('id')]);

                return true;
            }
        }

        return false;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public static function logout()
    {
        try {
            SessionModel::logoutSession(session_id());
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $password
     * @return void
     * @throws \Exception
     */
    public static function updatePassword($password)
    {
        try {
            $user = static::getAuthUser();
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            $password = password_hash($password, PASSWORD_BCRYPT);

            static::raw('UPDATE `@THIS` SET password = ? WHERE id = ?', [$password, $user->get('id')]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $email
     * @return void
     * @throws \Exception
     */
    public static function restorePassword($email)
    {
        try {
            $data = static::raw('SELECT * FROM `@THIS` WHERE email = ?', [$email])->first();
            if (!$data) {
                throw new \Exception(__('app.user_not_found', ['email' => $email]));
            }

            $reset_token = md5(random_bytes(55) . date('Y-m-d H:i:s'));

            static::raw('UPDATE `@THIS` SET password_reset = ? WHERE id = ?', [$reset_token, $data->get('id')]);

            $mailobj = new Asatru\SMTPMailer\SMTPMailer();
            $mailobj->setRecipient($email);
            $mailobj->setSubject(__('app.reset_password'));
            $mailobj->setView('mail/mail_layout', [['mail_content', 'mail/mailreset']], ['workspace' => app('workspace'), 'token' => $reset_token]);
            $mailobj->setProperties(mail_properties());
            $mailobj->send();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $reset_token
     * @param $password
     * @return void
     * @throws \Exception
     */
    public static function resetPassword($reset_token, $password)
    {
        try {
            $data = static::raw('SELECT * FROM `@THIS` WHERE password_reset = ?', [$reset_token])->first();
            if (!$data) {
                throw new \Exception('Token not found');
            }

            $password = password_hash($password, PASSWORD_BCRYPT);

            static::raw('UPDATE `@THIS` SET password_reset = NULL, password = ? WHERE id = ?', [$password, $data->get('id')]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $userId
     * @return mixed
     */
    public static function getUserById($userId)
    {
        try {
            $data = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$userId])->first();
            return $data;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return bool
     */
    public static function isCurrentlyAdmin()
    {
        try {
            $user = static::getAuthUser();
            if ((!$user) || (!$user->get('admin'))) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return null;
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
     * @param $email
     * @param $lang
     * @param $theme
     * @param $chatcolor
     * @param $show_log
     * @param $show_calendar_view
     * @param $show_plant_id
     * @param $notify_tasks_overdue
     * @param $notify_tasks_tomorrow
     * @param $notify_tasks_recurring
     * @param $notify_calendar_reminder
     * @param $show_plants_aoru
     * @param $remember_location_sorting
     * @param $weather_place
     * @param $color_scheme
     * @param $notify_plant_care
     * @param $push_tasks_overdue
     * @param $push_tasks_tomorrow
     * @param $push_tasks_recurring
     * @param $push_calendar_reminder
     * @param $push_chat_message
     * @param $push_plant_care
     * @param $preferred_locations array of Location ids, or null to leave
     *                              the user's existing filter untouched
     * @return void
     * @throws \Exception
     */
    public static function editPreferences($name, $email, $lang, $theme, $chatcolor, $show_log, $show_calendar_view, $show_plant_id, $notify_tasks_overdue, $notify_tasks_tomorrow, $notify_tasks_recurring, $notify_calendar_reminder, $show_plants_aoru, $remember_location_sorting, $weather_place = null, $color_scheme = null, $notify_plant_care = false, $push_tasks_overdue = false, $push_tasks_tomorrow = false, $push_tasks_recurring = false, $push_calendar_reminder = false, $push_chat_message = false, $push_plant_care = false, $preferred_locations = null)
    {
        try {
            $user = static::getAuthUser();
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            static::raw('UPDATE `@THIS` SET name = ?, email = ?, lang = ?, theme = ?, chatcolor = ?, show_log = ?, show_calendar_view = ?, show_plant_id = ?, notify_tasks_overdue = ?, notify_tasks_tomorrow = ?, notify_tasks_recurring = ?, notify_calendar_reminder = ?, show_plants_aoru = ?, remember_location_sorting = ?, weather_place = ?, color_scheme = ?, notify_plant_care = ?, push_tasks_overdue = ?, push_tasks_tomorrow = ?, push_tasks_recurring = ?, push_calendar_reminder = ?, push_chat_message = ?, push_plant_care = ? WHERE id = ?', [
                trim($name), trim($email), $lang, $theme, $chatcolor, $show_log, $show_calendar_view, $show_plant_id, $notify_tasks_overdue, $notify_tasks_tomorrow, $notify_tasks_recurring, $notify_calendar_reminder, (int)$show_plants_aoru, $remember_location_sorting, (is_numeric($weather_place) ? (int)$weather_place : null), (($color_scheme) && (array_key_exists($color_scheme, AppearanceModule::$available_themes)) ? $color_scheme : null), $notify_plant_care, $push_tasks_overdue, $push_tasks_tomorrow, $push_tasks_recurring, $push_calendar_reminder, $push_chat_message, $push_plant_care, $user->get('id')
            ]);

            if ($preferred_locations !== null) {
                UserPreferredLocationModel::setForUser($user->get('id'), $preferred_locations);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $flag
     * @return void
     * @throws \Exception
     */
    public static function updateListSortingPreferences($flag)
    {
        try {
            if ($flag) {
                setcookie('list_show_style', 'cards', time() + 31536000, '/');
                setcookie('list_sorting_style', 'name', time() + 31536000, '/');
                setcookie('list_order_style', 'desc', time() + 31536000, '/');
            } else {
                if (isset($_COOKIE['list_show_style'])) {
                    unset($_COOKIE['list_show_style']);
                    setcookie('list_show_style', '', 1, '/');
                }

                if (isset($_COOKIE['list_sorting_style'])) {
                    unset($_COOKIE['list_sorting_style']);
                    setcookie('list_sorting_style', '', 1, '/');
                }

                if (isset($_COOKIE['list_order_style'])) {
                    unset($_COOKIE['list_order_style']);
                    setcookie('list_order_style', '', 1, '/');
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $notes
     * @return void
     * @throws \Exception
     */
    public static function saveNotes($notes)
    {
        try {
            $user = static::getAuthUser();
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            static::raw('UPDATE `@THIS` SET notes = ? WHERE id = ?', [
                trim($notes), $user->get('id')
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public static function getNameById($id)
    {
        try {
            $user = static::getAuthUser();
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            $row = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();

            return $row?->get('name');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return string
     * @throws \Exception
     */
    public static function getEMailById($id)
    {
        try {
            $user = static::getAuthUser();
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            $row = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();

            return $row->get('email');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return string
     * @throws \Exception
     */
    public static function getChatColorForUser($id)
    {
        try {
            $user = static::getAuthUser();
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            $row = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();

            $color = $row?->get('chatcolor');
            if (($color === null) || (strlen($color) === 0)) {
                return '#7BC1DF';
            }

            return $color;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return void
     * @throws \Exception
     */
    public static function updateLastSeenMsg($id)
    {
        try {
            $user = static::getAuthUser();
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            static::raw('UPDATE `@THIS` SET last_seen_msg = ? WHERE id = ?', [$id, $user->get('id')]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return void
     * @throws \Exception
     */
    public static function updateLastSeenSysMsg($id)
    {
        try {
            $user = static::getAuthUser();
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            static::raw('UPDATE `@THIS` SET last_seen_sysmsg = ? WHERE id = ?', [$id, $user->get('id')]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public static function updateOnlineStatus()
    {
        try {
            $user = static::getAuthUser();
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            static::raw('UPDATE `@THIS` SET last_action = CURRENT_TIMESTAMP WHERE id = ?', [$user->get('id')]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public static function updateChatTyping()
    {
        try {
            $user = static::getAuthUser();
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            static::raw('UPDATE `@THIS` SET last_typing = CURRENT_TIMESTAMP WHERE id = ?', [$user->get('id')]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public static function isAnyoneTypingInChat()
    {
        try {
            $user = static::getAuthUser();
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            $rows = static::raw('SELECT * FROM `@THIS` WHERE id <> ?', [$user->get('id')]);
            foreach ($rows as $row) {
                if ((static::isUserOnline($row->get('id'))) && (UtilsModule::isTyping($row->get('last_typing')))) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return bool
     */
    public static function isUserOnline($id)
    {
        try {
            $row = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
            if (!$row) {
                return false;
            }

            return Carbon::parse($row->get('last_action'))->diffInMinutes() <= app('chat_timelimit', 15);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    public static function getOnlineUsers()
    {
        try {
            $result = [];

            $rows = static::raw('SELECT * FROM `@THIS`');
            foreach ($rows as $row) {
                if (static::isUserOnline($row->get('id'))) {
                    $result[] = $row;
                }
            }

            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return mixed
     */
    public static function getAll()
    {
        try {
            return static::raw('SELECT * FROM `@THIS`');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Turns on the current user's public wishlist share link, generating
     * a fresh opaque token the first time (or reusing the existing one on
     * a later call, so re-enabling doesn't invalidate a link someone
     * already has). Mirrors the password-reset token already used on
     * this table (see restorePassword) rather than using the user's
     * plain id, so the link can't be used to enumerate/guess other
     * users' wishlists.
     *
     * @return string The share token
     * @throws \Exception
     */
    public static function enableWishlistShare()
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            $token = $user->get('wishlist_share_token');
            if (!$token) {
                $token = md5(random_bytes(55) . date('Y-m-d H:i:s'));
            }

            static::raw('UPDATE `@THIS` SET wishlist_share_enable = 1, wishlist_share_token = ? WHERE id = ?', [$token, $user->get('id')]);

            return $token;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public static function disableWishlistShare()
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            static::raw('UPDATE `@THIS` SET wishlist_share_enable = 0 WHERE id = ?', [$user->get('id')]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Replaces the current user's wishlist share token with a new one,
     * invalidating any previously shared link, without changing whether
     * sharing is enabled.
     *
     * @return string The new share token
     * @throws \Exception
     */
    public static function regenerateWishlistShareToken()
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            $token = md5(random_bytes(55) . date('Y-m-d H:i:s'));

            static::raw('UPDATE `@THIS` SET wishlist_share_token = ? WHERE id = ?', [$token, $user->get('id')]);

            return $token;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $token
     * @return mixed
     * @throws \Exception
     */
    public static function getByWishlistShareToken($token)
    {
        try {
            if ((!is_string($token)) || (strlen($token) === 0)) {
                return null;
            }

            return static::raw('SELECT * FROM `@THIS` WHERE wishlist_share_token = ? AND wishlist_share_enable = 1', [$token])->first();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param array|null $ids When given, narrows the result down to admins
     *                        whose id is in this list (any id that isn't
     *                        actually an admin is silently ignored). A
     *                        null or empty list returns every admin.
     * @return mixed
     */
    public static function getAdmins($ids = null)
    {
        try {
            if ((is_array($ids)) && (count($ids) > 0)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));

                return static::raw('SELECT * FROM `@THIS` WHERE admin = 1 AND id IN (' . $placeholders . ')', $ids);
            }

            return static::raw('SELECT * FROM `@THIS` WHERE admin = 1');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $name
     * @param $email
     * @param $sendmail
     * @return mixed
     * @throws \Exception
     */
    public static function createUser($name, $email, $sendmail)
    {
        try {
            $password = substr(md5(random_bytes(55) . date('Y-m-d H:i:s')), 0, 10);
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Invalid E-Mail given: ' . $email);
            }

            static::raw('INSERT INTO `@THIS` (name, email, password) VALUES(?, ?, ?)', [
                $name, $email, password_hash($password, PASSWORD_BCRYPT)
            ]);
            
            if ($sendmail) {
                $mailobj = new Asatru\SMTPMailer\SMTPMailer();
                $mailobj->setRecipient($email);
                $mailobj->setSubject(__('app.account_created'));
                $mailobj->setView('mail/mail_layout', [['mail_content', 'mail/mailacccreated']], ['workspace' => app('workspace'), 'password' => $password]);
                $mailobj->setProperties(mail_properties());
                $mailobj->send();

                return null;
            }
            
            return $password;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @param $name
     * @param $email
     * @param $admin
     * @return void
     * @throws \Exception
     */
    public static function updateUser($id, $name, $email, $admin)
    {
        try {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Invalid E-Mail given: ' . $email);
            }
            
            static::raw('UPDATE `@THIS` SET name = ?, email = ?, admin = ? WHERE id = ?', [
                $name, $email, $admin, $id
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
    public static function removeUser($id)
    {
        try {
            SessionModel::clearForUser($id);

            static::raw('DELETE FROM `@THIS` WHERE id = ?', [
                $id
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}