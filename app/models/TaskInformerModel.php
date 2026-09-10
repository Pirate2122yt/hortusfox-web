<?php

/**
 * Class TaskInformerModel
 * 
 * Manages informing users of tasks having a due date
 */ 
class TaskInformerModel extends \Asatru\Database\Model {
    /**
     * @param $userId
     * @param $taskId
     * @param $what
     * @return bool
     * @throws \Exception
     */
    public static function userInformed($userId, $taskId, $what)
    {
        try {
            $data = static::raw('SELECT * FROM `@THIS` WHERE user = ? AND task = ? AND what = ?', [$userId, $taskId, $what])->first();
            if ($data) {
                return true;
            }

            return false;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $task
     * @param $what
     * @param $limit
     * @param $plant
     * @return void
     * @throws \Exception
     */
    public static function inform($task, $what, $limit = 5, $plant = null)
    {
        try {
            $users = UserModel::getAll();
            $count = 0;

            foreach ($users as $user) {
                $wantsEmail = (bool)$user->get('notify_tasks_' . $what);
                $wantsPush = (bool)$user->get('push_tasks_' . $what);

                // A task linked to a Plant carries that Plant's Location;
                // a task with no linked Plant has no Location to filter
                // on, so it always reaches whoever opted into push.
                if (($wantsPush) && ($plant)) {
                    $wantsPush = UserPreferredLocationModel::wantsLocation($user->get('id'), $plant->get('location'));
                }

                if ((($wantsEmail) || ($wantsPush)) && (!static::userInformed($user->get('id'), $task->get('id'), $what))) {
                    if ($count < $limit) {
                        $lang = $user->get('lang');
                        if ($lang === null) {
                            $lang = env('APP_LANG', 'en');
                        }

                        setLanguage($lang);

                        if ($wantsEmail) {
                            $mailobj = new Asatru\SMTPMailer\SMTPMailer();
                            $mailobj->setRecipient($user->get('email'));
                            $mailobj->setSubject('[' . __('app.mail_info_task_' . $what) . '] ' . $task->get('title'));
                            $mailobj->setView('mail/mail_layout', [['mail_content', 'mail/task_' . $what]], ['task' => $task, 'plant' => $plant, 'user' => $user]);
                            $mailobj->setProperties(mail_properties());
                            $mailobj->send();
                        }

                        if ($wantsPush) {
                            PushNotificationModule::sendToUser($user->get('id'), __('app.mail_info_task_' . $what), $task->get('title'), url('/tasks'));
                        }

                        static::raw('INSERT INTO `@THIS` (user, task, what) VALUES(?, ?, ?)', [$user->get('id'), $task->get('id'), $what]);

                        $count++;
                    }
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}