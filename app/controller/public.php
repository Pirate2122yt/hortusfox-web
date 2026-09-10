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
     * Maximum accepted upload size for the public identifier, in bytes.
     * Deliberately tighter than a typical admin-side upload limit, since
     * this endpoint is reachable without a session.
     */
    const MAX_UPLOAD_BYTES = 6 * 1024 * 1024;

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

			try {
				TextBlockModule::newPlantComment($plant->get('name'), url('/plants/details/' . $id), $name, $comment);
			} catch (\Exception $e) {
				// A failed chat notification shouldn't turn a successfully
				// saved comment into an error for the visitor - but it
				// shouldn't vanish silently either, or a real problem here
				// (a missing migration, chat disabled, a DB error) is
				// undiagnosable from the outside.
				addLog(ASATRU_LOG_ERROR, 'Failed to post chat notification for public comment: ' . $e->getMessage());
			}

			if (app('public_comment_notify_admins', true)) {
				try {
					PlantLogCommentModel::notifyAdmins($plant, $entry, $name, $comment);
				} catch (\Exception $e) {
					// Same reasoning as the chat notification above - a
					// failed admin email is logged, not surfaced to the
					// visitor whose comment was already saved fine.
					addLog(ASATRU_LOG_ERROR, 'Failed to email admins about public comment: ' . $e->getMessage());
				}
			}

			FlashMessage::setMsg('success', __('app.public_comment_posted'));
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
		}

		return redirect('/public/plant/' . $id . '#plant-log-entry-' . $log_entry);
	}

	/**
	 * Handles URL: /public/identify
	 *
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\ViewHandler
	 */
	public function identify_page($request)
	{
		return parent::view(['content', 'public_identify'], [
			'available' => static::identifyAvailable(),
			'remaining' => PublicIdentifyRequestModel::getRemaining(static::clientIp()),
			'results' => null
		]);
	}

	/**
	 * Handles URL: /public/identify (POST)
	 *
	 * Every safeguard here runs before the image ever reaches Pl@ntNet,
	 * since that's a shared, free API key paid for in request quota:
	 * the feature has its own admin opt-in, a hidden honeypot field, an
	 * optional CAPTCHA, a strict per-IP daily cap, and the upload itself
	 * is size-checked and content-verified as a real image before it's
	 * used or stored anywhere (however briefly).
	 *
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\ViewHandler
	 */
	public function identify_plant($request)
	{
		$ip = static::clientIp();
		$image_file = null;

		try {
			if (!static::identifyAvailable()) {
				throw new \Exception(__('app.public_identify_unavailable'));
			}

			// Honeypot: a real visitor never fills this hidden field in.
			// Pretend it worked rather than tipping a bot off.
			$honeypot = $request->params()->query('website', null);
			if ((is_string($honeypot)) && (strlen($honeypot) > 0)) {
				return parent::view(['content', 'public_identify'], [
					'available' => true,
					'remaining' => PublicIdentifyRequestModel::getRemaining($ip),
					'results' => []
				]);
			}

			if (!static::verifyCaptcha($request->params()->query('cf-turnstile-response', null), $ip)) {
				throw new \Exception(__('app.public_identify_captcha_failed'));
			}

			if (!PublicIdentifyRequestModel::tryConsume($ip)) {
				throw new \Exception(__('app.public_identify_rate_limited'));
			}

			if ((!isset($_FILES['photo'])) || ($_FILES['photo']['error'] !== UPLOAD_ERR_OK)) {
				throw new \Exception(__('app.public_identify_no_photo'));
			}

			if ($_FILES['photo']['size'] > self::MAX_UPLOAD_BYTES) {
				throw new \Exception(__('app.public_identify_photo_too_large'));
			}

			// Written next to the (equally short-lived) admin-facing
			// identify uploads, then removed again a few lines down
			// whether recognition succeeds or fails.
			$image_file = UtilsModule::uploadFile('photo', public_path() . '/img/');

			$data = RecognitionModule::identifyPublic($image_file);

			if ((!isset($data->results)) || (!is_array($data->results))) {
				throw new \Exception(__('app.public_identify_no_results'));
			}

			return parent::view(['content', 'public_identify'], [
				'available' => true,
				'remaining' => PublicIdentifyRequestModel::getRemaining($ip),
				'results' => $data->results
			]);
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());

			return parent::view(['content', 'public_identify'], [
				'available' => static::identifyAvailable(),
				'remaining' => PublicIdentifyRequestModel::getRemaining($ip),
				'results' => null
			]);
		} finally {
			if (($image_file) && (file_exists($image_file))) {
				unlink($image_file);
			}
		}
	}

	/**
	 * @return bool
	 */
	private static function identifyAvailable()
	{
		return (app('public_plantid_enable')) && (!empty(app('plantrec_apikey')));
	}

	/**
	 * @return string
	 */
	private static function clientIp()
	{
		return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
	}

	/**
	 * Verifies a Cloudflare Turnstile token, if the admin configured one.
	 * If no secret key is configured the CAPTCHA step is simply skipped
	 * (the daily IP cap, honeypot and upload validation still apply) -
	 * but once a secret key IS configured, a failure to verify (missing
	 * token, rejected token, or a network error reaching Cloudflare)
	 * always fails closed.
	 *
	 * @param $token
	 * @param $ip
	 * @return bool
	 */
	private static function verifyCaptcha($token, $ip)
	{
		$secret = app('public_captcha_secretkey');

		if (empty($secret)) {
			return true;
		}

		if ((!is_string($token)) || (strlen($token) === 0)) {
			return false;
		}

		try {
			$ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
				'secret' => $secret,
				'response' => $token,
				'remoteip' => $ip
			]));

			$response = curl_exec($ch);
			$error = curl_error($ch);

			curl_close($ch);

			if ((is_string($error)) && (strlen($error) > 0)) {
				return false;
			}

			$json = json_decode($response);

			return (bool)(($json && isset($json->success)) ? $json->success : false);
		} catch (\Exception $e) {
			return false;
		}
	}
}
