<?php

/**
 * Class CalendarInformerModel
 * 
 * Informs users of calendar events
 */ 
class CalendarInformerModel extends \Asatru\Database\Model {
    /**
     * @param $userId
     * @param $itemId
     * @return bool
     * @throws \Exception
     */
    public static function userInformed($userId, $itemId)
    {
        try {
            $data = static::raw('SELECT * FROM `@THIS` WHERE user = ? AND item = ?', [$userId, $itemId])->first();
            if ($data) {
                return true;
            }

            return false;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $item
     * @param $limit
     * @return void
     * @throws \Exception
     */
    public static function inform($item, $limit = 5)
    {
        try {
            $users = UserModel::getAll();
            $count = 0;

            foreach ($users as $user) {
                $wantsEmail = (bool)$user->get('notify_calendar_reminder');
                $wantsPush = (bool)$user->get('push_calendar_reminder');

                if ((($wantsEmail) || ($wantsPush)) && (!static::userInformed($user->get('id'), $item->get('id')))) {
                    if ($count < $limit) {
                        $lang = $user->get('lang');
                        if ($lang === null) {
                            $lang = env('APP_LANG', 'en');
                        }

                        setLanguage($lang);

                        if ($wantsEmail) {
                            $mailobj = new Asatru\SMTPMailer\SMTPMailer();
                            $mailobj->setRecipient($user->get('email'));
                            $mailobj->setSubject(__('app.mail_info_calendar_reminder'));
                            $mailobj->setView('mail/mail_layout', [['mail_content', 'mail/calendar_reminder']], ['item' => $item, 'user' => $user]);
                            $mailobj->setProperties(mail_properties());
                            $mailobj->send();
                        }

                        if ($wantsPush) {
                            PushNotificationModule::sendToUser($user->get('id'), __('app.mail_info_calendar_reminder'), $item->get('name'), url('/calendar'));
                        }

                        static::raw('INSERT INTO `@THIS` (user, item) VALUES(?, ?)', [$user->get('id'), $item->get('id')]);

                        $count++;
                    }
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}