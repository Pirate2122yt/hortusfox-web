<?php

/**
 * Class TextBlockModule
 * 
 * Manages system chat messages
 */
class TextBlockModule {
    /**
     * @param $name
     * @param $url
     * @return void
     * @throws \Exception
     */
    public static function newPlant($name, $url)
    {
        try {
            $text = __('tb.added_new_plant', ['name' => $name, 'url' => $url]);

            static::addToChat($text, 'x1fab4');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $name
     * @param $url
     * @return void
     * @throws \Exception
     */
    public static function plantToHistory($name, $url)
    {
        try {
            $text = __('tb.moved_plant_to_history', ['name' => $name, 'url' => $url, 'history' => app('history_name')]);

            static::addToChat($text, 'x1f570');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $name
     * @param $url
     * @return void
     * @throws \Exception
     */
    public static function plantFromHistory($name, $url)
    {
        try {
            $text = __('tb.restored_plant_from_history', ['name' => $name, 'url' => $url, 'history' => app('history_name')]);

            static::addToChat($text, 'x1f570');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $name
     * @return void
     * @throws \Exception
     */
    public static function deletePlant($name)
    {
        try {
            $text = __('tb.deleted_plant', ['name' => $name]);

            static::addToChat($text, 'x1fab4');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $name
     * @param $url
     * @return void
     * @throws \Exception
     */
    public static function createdTask($name, $url)
    {
        try {
            $text = __('tb.created_task', ['name' => $name, 'url' => $url]);

            static::addToChat($text, 'x1f4dc');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $name
     * @param $url
     * @return void
     * @throws \Exception
     */
    public static function completedTask($name, $url)
    {
        try {
            $text = __('tb.completed_task', ['name' => $name, 'url' => $url]);

            static::addToChat($text, 'x1f4dc');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $name
     * @param $url
     * @return void
     * @throws \Exception
     */
    public static function reactivatedTask($name, $url)
    {
        try {
            $text = __('tb.reactivated_task', ['name' => $name, 'url' => $url]);

            static::addToChat($text, 'x1f4dc');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $name
     * @param $url
     * @return void
     * @throws \Exception
     */
    public static function createdInventoryItem($name, $url)
    {
        try {
            $text = __('tb.created_inventory_item', ['name' => $name, 'url' => $url]);

            static::addToChat($text, 'x1f4d6');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $name
     * @return void
     * @throws \Exception
     */
    public static function removedInventoryItem($name)
    {
        try {
            $text = __('tb.removed_inventory_item', ['name' => $name]);

            static::addToChat($text, 'x1f4d6');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $name
     * @param $url
     * @return void
     * @throws \Exception
     */
    public static function addedCalendarItem($name, $url)
    {
        try {
            $text = __('tb.added_calendar_item', ['name' => $name, 'url' => $url]);

            static::addToChat($text, 'x1f4c5');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $name
     * @param $url
     * @return void
     * @throws \Exception
     */
    public static function editedCalendarItem($name, $url)
    {
        try {
            $text = __('tb.edited_calendar_item', ['name' => $name, 'url' => $url]);

            static::addToChat($text, 'x1f4c5');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $name
     * @param $url
     * @param $authorName
     * @param $comment
     * @return void
     * @throws \Exception
     */
    public static function newPlantComment($name, $url, $authorName, $comment)
    {
        try {
            $preview = (mb_strlen($comment) > 120) ? (mb_substr($comment, 0, 120) . '…') : $comment;

            // The commenter is an anonymous visitor on the public catalogue,
            // not a signed-in user - their name and comment text are
            // untrusted input. addToChat()'s message is rendered straight
            // into innerHTML by the live chat poll (app.js
            // renderNewChatMessage), so anything left unescaped here would
            // be a stored-XSS vector against every signed-in user's browser.
            // The plant name/URL are not user input at this call site, so
            // they're left as-is to match every other message in this file.
            $authorName = trim((string)$authorName);
            $authorName = htmlspecialchars(($authorName !== '') ? $authorName : __('app.public_comment_anonymous'), ENT_QUOTES, 'UTF-8');
            $preview = htmlspecialchars($preview, ENT_QUOTES, 'UTF-8');

            $text = __('tb.new_plant_comment', ['name' => $name, 'url' => $url, 'author' => $authorName, 'comment' => $preview]);

            static::addToChat($text, 'x1f4ac', true, __('app.chat_public_comment_label'));
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $message
     * @param $icon
     * @param $api
     * @param $displayName Overrides the "posted by" label shown in the
     *                      chat tab with a fixed string instead of the
     *                      signed-in user's name. Also forces userId to
     *                      0 regardless of the ambient session, since a
     *                      message using this is never really "from"
     *                      whichever staff member's browser happened to
     *                      trigger it (e.g. an admin testing the public
     *                      comment form themselves would otherwise get
     *                      credited with visitors' comments, since a
     *                      public-page request can still carry an
     *                      admin's logged-in session cookie).
     * @return void
     * @throws \Exception
     */
    public static function addToChat($message, $icon, $api = false, $displayName = null)
    {
        try {
            if (!app('chat_system')) {
                return;
            }

            $user = ($displayName === null) ? UserModel::getAuthUser() : null;
            if ((!$user) && (!$api)) {
                throw new \Exception('Invalid user');
            }

            $icon = html_entity_decode('&#' . $icon, ENT_COMPAT | ENT_QUOTES);

            ChatMsgModel::raw('INSERT INTO `@THIS` (userId, message, sysmsg, display_name, created_at) VALUES(?, ?, 1, ?, CURRENT_TIMESTAMP)', [
                (($user) ? $user->get('id') : 0),
                $icon . ' ' . $message,
                $displayName
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
