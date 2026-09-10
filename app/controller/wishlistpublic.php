<?php

/**
 * Class WishlistPublicController
 *
 * A single, read-only, no-login route: a gift-registry-style view of one
 * user's wishlist, reachable only through their opaque share token (see
 * UserModel::enableWishlistShare). Mirrors PublicController's shape -
 * everything here is read-only and only ever exposes the wishlist of the
 * user identified by that token.
 */
class WishlistPublicController extends BaseController {
    const INDEX_LAYOUT = 'public_layout';

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
	 * Handles URL: /wishlist/share/{token}
	 *
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\ViewHandler
	 */
	public function view_shared($request)
	{
		$token = $request->arg('token');

		$owner = UserModel::getByWishlistShareToken($token);

		if (!$owner) {
			return parent::view(['content', 'public_wishlist'], [
				'owner' => null,
				'items' => [],
				'locations_by_id' => [],
				'total_price' => 0
			]);
		}

		$items = WishlistModel::getForUser($owner->get('id'));

		$locations_by_id = [];
		if (is_countable($items)) {
			foreach ($items as $wishlist_item) {
				if (($wishlist_item->get('location')) && (!isset($locations_by_id[$wishlist_item->get('location')]))) {
					$location = LocationsModel::getLocationById($wishlist_item->get('location'));
					$locations_by_id[$wishlist_item->get('location')] = $location ? $location->get('name') : null;
				}
			}
		}

		return parent::view(['content', 'public_wishlist'], [
			'owner' => $owner,
			'items' => $items,
			'locations_by_id' => $locations_by_id,
			'total_price' => WishlistModel::getTotalPrice($items)
		]);
	}
}
