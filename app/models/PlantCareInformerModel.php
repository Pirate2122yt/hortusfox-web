<?php

/**
 * Class PlantCareInformerModel
 *
 * Manages informing users when a plant's watering, fertilising or
 * repotting interval has elapsed. Unlike TaskInformerModel's
 * inform-once-ever dedup, this is cycle-based: a (plant, user, action)
 * combination is only re-notified once the plant's underlying last_X
 * care date has actually moved on (i.e. someone logged the care action
 * again and it has since become due once more).
 */
class PlantCareInformerModel extends \Asatru\Database\Model {
    /**
     * @param $plantId
     * @param $userId
     * @param $action
     * @param $dueSince the care-cycle marker (last_X date, or the plant's
     *                  created_at if care was never logged) this due
     *                  occurrence is based on
     * @return bool
     * @throws \Exception
     */
    public static function alreadyInformed($plantId, $userId, $action, $dueSince)
    {
        try {
            $row = static::raw('SELECT * FROM `@THIS` WHERE plant = ? AND user = ? AND action = ?', [$plantId, $userId, $action])->first();
            if (!$row) {
                return false;
            }

            $stored = $row->get('notified_for');

            if (($stored === null) || ($dueSince === null)) {
                return $stored === $dueSince;
            }

            return strtotime($stored) === strtotime($dueSince);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $plantId
     * @param $userId
     * @param $action
     * @param $dueSince
     * @return void
     * @throws \Exception
     */
    private static function markInformed($plantId, $userId, $action, $dueSince)
    {
        try {
            $row = static::raw('SELECT * FROM `@THIS` WHERE plant = ? AND user = ? AND action = ?', [$plantId, $userId, $action])->first();

            if ($row) {
                static::raw('UPDATE `@THIS` SET notified_for = ?, created_at = CURRENT_TIMESTAMP WHERE id = ?', [$dueSince, $row->get('id')]);
            } else {
                static::raw('INSERT INTO `@THIS` (plant, user, action, notified_for) VALUES(?, ?, ?, ?)', [$plantId, $userId, $action, $dueSince]);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Emails every user who has opted in to plant care reminders and
     * hasn't already been informed about this particular due occurrence.
     * A failed send for one user never blocks the others, since this is
     * only ever invoked from the cron job.
     *
     * @param $plant
     * @param $action one of 'water', 'fertilise', 'repot'
     * @param $dueSince
     * @param $limit
     * @return void
     * @throws \Exception
     */
    public static function inform($plant, $action, $dueSince, $limit = 5)
    {
        try {
            $users = UserModel::getAll();
            $count = 0;

            foreach ($users as $user) {
                if (($user->get('notify_plant_care')) && (!static::alreadyInformed($plant->get('id'), $user->get('id'), $action, $dueSince))) {
                    if ($count < $limit) {
                        $lang = $user->get('lang');
                        if ($lang === null) {
                            $lang = env('APP_LANG', 'en');
                        }

                        setLanguage($lang);

                        $mailobj = new Asatru\SMTPMailer\SMTPMailer();
                        $mailobj->setRecipient($user->get('email'));
                        $mailobj->setSubject('[' . __('app.mail_info_plant_care_due') . '] ' . $plant->get('name') . ' - ' . __('app.care_action_' . $action));
                        $mailobj->setView('mail/mail_layout', [['mail_content', 'mail/plant_care_due']], ['plant' => $plant, 'action' => $action, 'user' => $user]);
                        $mailobj->setProperties(mail_properties());
                        $mailobj->send();

                        static::markInformed($plant->get('id'), $user->get('id'), $action, $dueSince);

                        $count++;
                    }
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
