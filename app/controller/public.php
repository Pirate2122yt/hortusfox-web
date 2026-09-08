<?php

/**
 * Class PublicController
 *
 * The public, read-only plant catalogue. Every route here is reachable
 * without a session (see the '/public' allowance in _base.php) and must
 * only ever read plants/entries that are explicitly marked is_public -
 * never anything else, and never anything that writes except the
 * comment form below.
 */
class PublicController extends BaseController {
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
	 * Handles URL: /public
	 *
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\ViewHandler
	 */
	public function view_catalog($request)
	{
		$plants = PlantsModel::getPublicPlants();

		return parent::view(['content', 'public_catalog'], [
			'plants' => $plants
		]);
	}

	/**
	 * Handles URL: /public/plant/{id}
	 *
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\ViewHandler
	 */
	public function view_plant($request)
	{
		$id = $request->arg('id');

		$plant = PlantsModel::getDetails($id);

		if ((!$plant) || (!$plant->get('is_public'))) {
			return redirect('/public');
		}

		$photos = PlantPhotoModel::getPlantGallery($id);
		$log_entries = PlantLogModel::getPublicLogEntries($id);

		$log_entry_photos = [];
		$log_entry_comments = [];

		if (is_countable($log_entries)) {
			foreach ($log_entries as $log_entry) {
				$entry_photos = PlantLogPhotoModel::getForEntry($log_entry->get('id'));
				$entry_photos_arr = [];

				if (is_countable($entry_photos)) {
					foreach ($entry_photos as $entry_photo) {
						$entry_photos_arr[] = ['thumb' => $entry_photo->get('thumb'), 'original' => $entry_photo->get('original')];
					}
				}

				$log_entry_photos[$log_entry->get('id')] = $entry_photos_arr;
				$log_entry_comments[$log_entry->get('id')] = PlantLogCommentModel::getForEntry($log_entry->get('id'));
			}
		}

		return parent::view(['content', 'public_plant'], [
			'plant' => $plant,
			'photos' => $photos,
			'log_entries' => $log_entries,
			'log_entry_photos' => $log_entry_photos,
			'log_entry_comments' => $log_entry_comments
		]);
	}

	/**
	 * Handles URL: /public/plant/{id}/comment
	 *
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function add_comment($request)
	{
		$id = $request->arg('id');

		$plant = PlantsModel::getDetails($id);

		if ((!$plant) || (!$plant->get('is_public'))) {
			return redirect('/public');
		}

		$log_entry = $request->params()->query('entry', null);
		$name = $request->params()->query('name', null);
		$comment = $request->params()->query('comment', null);

		// Honeypot: a real visitor never fills this hidden field in, a
		// bot filling every field usually does. Pretend it worked and
		// silently drop it rather than tipping the bot off.
		$honeypot = $request->params()->query('website', null);

		if ((is_string($honeypot)) && (strlen($honeypot) > 0)) {
			return redirect('/public/plant/' . $id . '#plant-log-entry-' . $log_entry);
		}

		try {
			$entry = PlantLogModel::raw('SELECT * FROM `PlantLogModel` WHERE id = ? AND plant = ? AND is_system = 0', [$log_entry, $id])->first();

			if (!$entry) {
				throw new \Exception('Invalid journal entry');
			}

			PlantLogCommentModel::addComment($log_entry, $name, $comment);

			FlashMessage::setMsg('success', __('app.public_comment_posted'));
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
		}

		return redirect('/public/plant/' . $id . '#plant-log-entry-' . $log_entry);
	}
}
