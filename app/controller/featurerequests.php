<?php

/**
 * Class FeatureRequestsController
 *
 * Gateway to the feature request board
 */
class FeatureRequestsController extends BaseController {
    const INDEX_LAYOUT = 'layout';

	/**
	 * Perform base initialization
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct(self::INDEX_LAYOUT);
	}

	/**
	 * Handles URL: /feature-requests
	 *
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\ViewHandler
	 */
	public function view_feature_requests($request)
	{
		$user = UserModel::getAuthUser();

		$status_filter = $request->params()->query('status', null);

		$requests = FeatureRequestModel::getAll($status_filter);

		$requesters = [];
		$voted = [];
		if (is_countable($requests)) {
			foreach ($requests as $req) {
				if (!isset($requesters[$req->get('user')])) {
					$requester = UserModel::getUserById($req->get('user'));
					$requesters[$req->get('user')] = $requester ? $requester->get('name') : null;
				}

				$voted[$req->get('id')] = ($user) ? FeatureRequestVoteModel::hasVoted($req->get('id'), $user->get('id')) : false;
			}
		}

		return parent::view(['content', 'featurerequests'], [
			'user' => $user,
			'requests' => $requests,
			'requesters' => $requesters,
			'voted' => $voted,
			'status_filter' => $status_filter,
			'statuses' => FeatureRequestModel::$statuses
		]);
	}

	/**
	 * Handles URL: /feature-requests/add
	 *
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function add_feature_request($request)
	{
		try {
			$title = $request->params()->query('title');
			$description = $request->params()->query('description', '');

			FeatureRequestModel::addRequest($title, $description);

			return redirect('/feature-requests');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return redirect('/feature-requests');
		}
	}

	/**
	 * Handles URL: /feature-requests/edit
	 *
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function edit_feature_request($request)
	{
		try {
			$item = $request->params()->query('item');
			$title = $request->params()->query('title');
			$description = $request->params()->query('description', '');

			FeatureRequestModel::editRequest($item, $title, $description);

			return redirect('/feature-requests');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return redirect('/feature-requests');
		}
	}

	/**
	 * Handles URL: /feature-requests/remove
	 *
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function remove_feature_request($request)
	{
		try {
			$item = $request->params()->query('item');

			FeatureRequestModel::removeRequest($item);

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
	 * Handles URL: /feature-requests/status
	 *
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function set_feature_request_status($request)
	{
		try {
			$item = $request->params()->query('item');
			$status = $request->params()->query('status');

			FeatureRequestModel::setStatus($item, $status);

			return json([
				'code' => 200,
				'status' => $status
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}

	/**
	 * Handles URL: /feature-requests/vote
	 *
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function vote_feature_request($request)
	{
		try {
			$item = $request->params()->query('item');

			$count = FeatureRequestModel::toggleVote($item);

			$user = UserModel::getAuthUser();

			return json([
				'code' => 200,
				'count' => $count,
				'voted' => FeatureRequestVoteModel::hasVoted($item, $user->get('id'))
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}
}
