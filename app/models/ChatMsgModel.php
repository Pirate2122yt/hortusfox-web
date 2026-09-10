<?php

/**
 * Class ChatMsgModel
 * 
 * Manages chat messages
 */ 
class ChatMsgModel extends \Asatru\Database\Model {
    /**
     * @param $message
     * @return void
     * @throws \Exception
     */
    public static function addMessage($message)
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            $message = trim($message);

            static::raw('INSERT INTO `@THIS` (userId, message) VALUES(?, ?)', [
                $user->get('id'), $message
            ]);

            try {
                static::notifyChatSubscribers($user, $message);
            } catch (\Exception $e) {
                // A failed push notification shouldn't turn a successfully
                // posted chat message into an error for the sender.
                addLog(ASATRU_LOG_ERROR, 'Failed to send chat push notifications: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Pushes a notification to every user who has opted in, other than
     * whoever just sent the message. Skips users who are currently online
     * (per the same chat_timelimit-based presence check the chat UI itself
     * uses), since they'll see the new message live without a push.
     *
     * @param $sender
     * @param $message
     * @return void
     * @throws \Exception
     */
    private static function notifyChatSubscribers($sender, $message)
    {
        $preview = preg_replace('/\s+/', ' ', $message);
        if (strlen($preview) > 120) {
            $preview = substr($preview, 0, 117) . '...';
        }

        $users = UserModel::getAll();

        foreach ($users as $user) {
            if ($user->get('id') == $sender->get('id')) {
                continue;
            }

            if ((!$user->get('push_chat_message')) || (UserModel::isUserOnline($user->get('id')))) {
                continue;
            }

            PushNotificationModule::sendToUser($user->get('id'), $sender->get('name'), $preview, url('/chat'));
        }
    }

    /**
     * @param $limit
     * @param $api
     * @return mixed
     * @throws \Exception
     */
    public static function getChat($limit = 50, $api = false)
    {
        try {
            $result = static::raw('SELECT * FROM `@THIS` ORDER BY created_at DESC LIMIT ' . safe_int($limit, 50));

            if (!$api) {
                if (count($result) > 0) {
                    UserModel::updateLastSeenMsg($result->get(0)->get('id'));

                    $lastsysmsg = static::raw('SELECT * FROM `@THIS` WHERE sysmsg = 1 ORDER BY created_at DESC')->first();
                    if ($lastsysmsg) {
                        UserModel::updateLastSeenSysMsg($lastsysmsg->get('id'));
                    }
                }
            }

            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public static function getLatestMessages()
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            $result = static::raw('SELECT * FROM `@THIS` WHERE id > ? ORDER BY created_at DESC', [($user->get('last_seen_msg')) ? $user->get('last_seen_msg') : 0]);

            if (($result) && (count($result) > 0)) {
                UserModel::updateLastSeenMsg($result->get(0)->get('id'));
            }

            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $limit
     * @return mixed
     * @throws \Exception
     */
    public static function getLatestSystemMessage($limit = 0)
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            $limit_token = '';
            if ($limit > 0) {
                $limit_token = 'LIMIT ' . strval($limit);
            }

            $result = static::raw('SELECT * FROM `@THIS` WHERE sysmsg = 1 AND id > ? ORDER BY created_at DESC ' . $limit_token, [($user->get('last_seen_sysmsg')) ? $user->get('last_seen_sysmsg') : 0]);
            if (($result) && (count($result) > 0)) {
                $msg = $result->get(count($result) - 1);

                UserModel::updateLastSeenSysMsg($msg->get('id'));
                
                return $msg;
            }

            return null;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * System messages only, newest first, for the /api/activity/rss
     * feed. Unlike getChat()/getLatestSystemMessage() this never
     * touches a user's last_seen_msg/last_seen_sysmsg pointers -
     * there's no ambient logged-in user on a token-authed API request,
     * and reading the feed shouldn't mark anything as seen in the
     * in-app chat tab anyway.
     *
     * @param $limit
     * @return mixed
     * @throws \Exception
     */
    public static function getSystemMessagesForFeed($limit = 50)
    {
        try {
            return static::raw('SELECT * FROM `@THIS` WHERE sysmsg = 1 ORDER BY created_at DESC LIMIT ' . safe_int($limit, 50));
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return int
     * @throws \Exception
     */
    public static function getUnreadCount()
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            $data = static::raw('SELECT COUNT(*) AS `count` FROM `@THIS` WHERE userId <> ? AND id > ? ORDER BY id ASC', [
                $user->get('id'), ($user->get('last_seen_msg')) ? $user->get('last_seen_msg') : 0
            ])->first();

            return $data->get('count');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public static function getMessageById($id)
    {
        try {
            return static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Edits a user-typed chat message (never a system message) and posts a
     * new system message recording the original text and its author, so
     * the change stays visible in the chat history rather than silently
     * overwriting it. Authorization (only the message's own author may
     * edit it) is the caller's responsibility - this assumes it has
     * already been checked.
     *
     * @param $id
     * @param $newMessage
     * @return bool true if a message was found and edited
     * @throws \Exception
     */
    public static function editMessage($id, $newMessage)
    {
        try {
            $row = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
            if ((!$row) || ($row->get('sysmsg'))) {
                return false;
            }

            $newMessage = trim($newMessage);
            if (strlen($newMessage) === 0) {
                throw new \Exception(__('app.chat_message_empty'));
            }

            if ($newMessage === $row->get('message')) {
                return true;
            }

            $authorName = $row->get('display_name') ?: UserModel::getNameById($row->get('userId'));

            static::raw('UPDATE `@THIS` SET message = ? WHERE id = ?', [$newMessage, $id]);

            static::raw('INSERT INTO `@THIS` (userId, message, sysmsg, created_at) VALUES(?, ?, 1, CURRENT_TIMESTAMP)', [
                $row->get('userId'),
                __('app.chat_message_edited_sysmsg', ['user' => $authorName, 'original' => $row->get('message')])
            ]);

            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Deletes a single chat entry - a user-typed message or a system
     * message (activity log entry) alike, since both are rows in this
     * same table distinguished only by the sysmsg flag. Admin-only,
     * enforced by the caller (ChatController::remove_message).
     *
     * @param $id
     * @return bool true if a row was actually removed
     * @throws \Exception
     */
    public static function removeMessage($id)
    {
        try {
            $row = static::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
            if (!$row) {
                return false;
            }

            static::raw('DELETE FROM `@THIS` WHERE id = ?', [$id]);

            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}