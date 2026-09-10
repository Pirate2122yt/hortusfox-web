<?php

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 * Class PushNotificationModule
 *
 * Thin wrapper around minishlink/web-push. Sending is always best-effort:
 * a failure here (missing config, an expired subscription, a network
 * hiccup) is logged and never allowed to bubble up to the caller, since
 * a push notification is a nice-to-have, not something that should ever
 * break the action that triggered it.
 */
class PushNotificationModule {
    /**
     * @return bool
     */
    public static function isConfigured()
    {
        return (bool)app('push_enable')
            && (is_string(app('vapid_public_key'))) && (strlen(app('vapid_public_key')) > 0)
            && (is_string(app('vapid_private_key'))) && (strlen(app('vapid_private_key')) > 0);
    }

    /**
     * @return WebPush
     * @throws \Exception
     */
    private static function client()
    {
        $subject = app('vapid_subject');

        if ((!is_string($subject)) || (strlen(trim($subject)) === 0)) {
            $fromaddress = app('smtp_fromaddress');
            $subject = (is_string($fromaddress)) && (strlen($fromaddress) > 0) ? ('mailto:' . $fromaddress) : url('/');
        }

        return new WebPush([
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => app('vapid_public_key'),
                'privateKey' => app('vapid_private_key')
            ]
        ]);
    }

    /**
     * Sends a push notification to every device the given user has
     * subscribed from. Best-effort: never throws to the caller, and
     * automatically forgets subscriptions the browser reports as expired.
     *
     * @param $userId
     * @param $title
     * @param $body
     * @param $url
     * @return void
     */
    public static function sendToUser($userId, $title, $body, $url = '/')
    {
        try {
            if (!static::isConfigured()) {
                return;
            }

            $subscriptions = PushSubscriptionModel::getForUser($userId);
            if ((!is_countable($subscriptions)) || (count($subscriptions) === 0)) {
                return;
            }

            $payload = json_encode([
                'title' => $title,
                'body' => $body,
                'url' => $url,
                'icon' => '/logo.png',
                'badge' => '/logo.png'
            ]);

            $webPush = static::client();

            foreach ($subscriptions as $subscription) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $subscription->get('endpoint'),
                        'keys' => [
                            'p256dh' => $subscription->get('p256dh'),
                            'auth' => $subscription->get('auth_token')
                        ]
                    ]),
                    $payload
                );
            }

            foreach ($webPush->flush() as $report) {
                if ((!$report->isSuccess()) && ($report->isSubscriptionExpired())) {
                    try {
                        PushSubscriptionModel::removeByEndpoint($report->getEndpoint());
                    } catch (\Exception $e) {
                        addLog(ASATRU_LOG_ERROR, 'Failed to remove expired push subscription: ' . $e->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            addLog(ASATRU_LOG_ERROR, 'Failed to send push notification: ' . $e->getMessage());
        }
    }
}
