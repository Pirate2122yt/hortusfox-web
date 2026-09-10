<?php

/**
 * Class WishlistController
 *
 * The plant wishlist: per-user entries for things a user wants to get
 * but doesn't own yet. Every signed-in user can browse three views -
 * their own ("mine"), everyone's combined ("overall"), and grouped by
 * intended Place ("place") - but only an entry's owner (or an admin) may
 * edit, remove, or move it into the real Plants collection.
 */
class WishlistController extends BaseController {
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
	 * Handles URL: /wishlist
	 *
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\ViewHandler
	 */
	public function index($request)
	{
		$user = UserModel::getAuthUser();

		$view = $request->params()->query('view', 'mine');
		if (!in_array($view, ['mine', 'overall', 'place'])) {
			$view = 'mine';
		}

		$places_grouped = [];

		if ($view === 'mine') {
			$items = WishlistModel::getForUser($user->get('id'));
		} else if ($view === 'overall') {
			$items = WishlistModel::getAll();
		} else {
			$located_items = WishlistModel::getWithLocation();
			$unplaced_items = [];

			if (is_countable($located_items)) {
				foreach ($located_items as $located_item) {
					$location = LocationsModel::getLocationById($located_item->get('location'));
					$place_id = ($location) ? $location->get('place') : null;

					if (!$place_id) {
						$unplaced_items[] = $located_item;
						continue;
					}

					if (!isset($places_grouped[$place_id])) {
						$place = PlacesModel::getById($place_id);

						$places_grouped[$place_id] = [
							'place' => $place,
							'items' => []
						];
					}

					$places_grouped[$place_id]['items'][] = $located_item;
				}
			}

			$items = $located_items;

			if (count($unplaced_items) > 0) {
				$places_grouped[0] = [
					'place' => null,
					'items' => $unplaced_items
				];
			}
		}

		$owners = [];
		$locations_by_id = [];

		if (is_countable($items)) {
			foreach ($items as $wishlist_item) {
				if (!isset($owners[$wishlist_item->get('user')])) {
					$owner = UserModel::getUserById($wishlist_item->get('user'));
					$owners[$wishlist_item->get('user')] = $owner ? $owner->get('name') : null;
				}

				if (($wishlist_item->get('location')) && (!isset($locations_by_id[$wishlist_item->get('location')]))) {
					$location = LocationsModel::getLocationById($wishlist_item->get('location'));
					$locations_by_id[$wishlist_item->get('location')] = $location ? $location->get('name') : null;
				}
			}
		}

		return parent::view(['content', 'wishlist'], [
			'user' => $user,
			'view' => $view,
			'items' => $items,
			'places_grouped' => $places_grouped,
			'owners' => $owners,
			'locations_by_id' => $locations_by_id,
			'locations' => LocationsModel::getAll(),
			'total_price' => WishlistModel::getTotalPrice($items),
			'priorities' => WishlistModel::$priorities,
			'share_url' => ($user->get('wishlist_share_enable')) ? workspace_url('/wishlist/share/' . $user->get('wishlist_share_token')) : null
		]);
	}

	/**
	 * Handles URL: /wishlist/add
	 *
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function add_item($request)
	{
		try {
			$id = WishlistModel::addItem(
				$request->params()->query('name'),
				$request->params()->query('species', ''),
				$request->params()->query('cultivar', ''),
				$request->params()->query('notes', ''),
				$request->params()->query('priority', WishlistModel::PRIORITY_WOULD_LIKE),
				$request->params()->query('location', null),
				$request->params()->query('source_url', ''),
				$request->params()->query('price', ''),
				$request->params()->query('best_time_note', '')
			);

			if (($id) && (isset($_FILES['photo'])) && ($_FILES['photo']['error'] === UPLOAD_ERR_OK)) {
				try {
					WishlistModel::uploadPhoto($id);
				} catch (\Exception $e) {
					addLog(ASATRU_LOG_ERROR, 'Failed to upload photo for new wishlist item ' . $id . ': ' . $e->getMessage());
				}
			}

			FlashMessage::setMsg('success', __('app.wishlist_item_added'));
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
		}

		return back();
	}

	/**
	 * Handles URL: /wishlist/edit
	 *
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function edit_item($request)
	{
		try {
			$id = $request->params()->query('item');

			WishlistModel::editItem(
				$id,
				$request->params()->query('name'),
				$request->params()->query('species', ''),
				$request->params()->query('cultivar', ''),
				$request->params()->query('notes', ''),
				$request->params()->query('priority', WishlistModel::PRIORITY_WOULD_LIKE),
				$request->params()->query('location', null),
				$request->params()->query('source_url', ''),
				$request->params()->query('price', ''),
				$request->params()->query('best_time_note', '')
			);

			if ((isset($_FILES['photo'])) && ($_FILES['photo']['error'] === UPLOAD_ERR_OK)) {
				WishlistModel::uploadPhoto($id);
			}

			FlashMessage::setMsg('success', __('app.wishlist_item_saved'));
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
		}

		return back();
	}

	/**
	 * Handles URL: /wishlist/remove
	 *
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function remove_item($request)
	{
		try {
			WishlistModel::removeItem($request->params()->query('item'));

			FlashMessage::setMsg('success', __('app.wishlist_item_removed'));
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
		}

		return back();
	}

	/**
	 * Handles URL: /wishlist/share/toggle
	 *
	 * Turns the current user's public wishlist share link on or off.
	 * Used from the Preferences modal rather than the wishlist page
	 * itself.
	 *
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function toggle_share($request)
	{
		try {
			$enable = (bool)$request->params()->query('enable', 0);

			if ($enable) {
				UserModel::enableWishlistShare();
			} else {
				UserModel::disableWishlistShare();
			}
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
		}

		return back();
	}

	/**
	 * Handles URL: /wishlist/share/regenerate
	 *
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function regenerate_share($request)
	{
		try {
			UserModel::regenerateWishlistShareToken();

			FlashMessage::setMsg('success', __('app.wishlist_share_link_regenerated'));
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
		}

		return back();
	}
}
