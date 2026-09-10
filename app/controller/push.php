<?php

/**
 * Class PushController
 *
 * Gateway to Web Push subscription management. All methods are JSON-only
 * and require an authenticated session, which BaseController enforces for
 * every route this controller doesn't explicitly whitelist.
 */
class PushController extends BaseController {
    /**
	 * Perform base initialization
	 *
	 * @return void
	 */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Handles URL: /push/subscribe
     *
     * @param Asatru\Controller\ControllerArg $request
     * @return Asatru\View\JsonHandler
     */
    public function subscribe($request)
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            $body = json_decode(file_get_contents('php://input'), true);

            $endpoint = $body['endpoint'] ?? null;
            $p256dh = $body['keys']['p256dh'] ?? null;
            $authToken = $body['keys']['auth'] ?? null;

            if ((!is_string($endpoint)) || (strlen($endpoint) === 0) || (!is_string($p256dh)) || (!is_string($authToken))) {
                throw new \Exception('Invalid subscription data');
            }

            PushSubscriptionModel::subscribe($user->get('id'), $endpoint, $p256dh, $authToken, $_SERVER['HTTP_USER_AGENT'] ?? null);

            return json([
                'code' => 200
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handles URL: /push/unsubscribe
     *
     * @param Asatru\Controller\ControllerArg $request
     * @return Asatru\View\JsonHandler
     */
    public function unsubscribe($request)
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            $body = json_decode(file_get_contents('php://input'), true);

            $endpoint = $body['endpoint'] ?? null;

            if ((!is_string($endpoint)) || (strlen($endpoint) === 0)) {
                throw new \Exception('Invalid subscription data');
            }

            PushSubscriptionModel::removeByEndpoint($endpoint);

            return json([
                'code' => 200
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handles URL: /push/test
     *
     * @param Asatru\Controller\ControllerArg $request
     * @return Asatru\View\JsonHandler
     */
    public function test($request)
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            if (!PushNotificationModule::isConfigured()) {
                throw new \Exception(__('app.push_not_configured'));
            }

            PushNotificationModule::sendToUser($user->get('id'), __('app.push_test_title'), __('app.push_test_body'), url('/'));

            return json([
                'code' => 200
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => $e->getMessage()
            ]);
        }
    }
}
