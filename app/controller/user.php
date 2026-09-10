<?php

/**
 * Class UserController
 * 
 * Gateway to user management
 */
class UserController extends BaseController {
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
	 * Handles URL: /profile
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\ViewHandler
	 */
	public function view_profile($request)
	{
		$user = UserModel::getAuthUser();
		$plants = PlantsModel::getAuthoredPlants($user->get('id'), 40);
		$log = LogModel::getHistory($user->get('id'));
		$sharelog = ShareLogModel::getForUser($user->get('id'));
		
		return parent::view(['content', 'profile'], [
			'user' => $user,
			'plants' => $plants,
			'log' => $log,
			'sharelog' => $sharelog
		]);
	}

	/**
	 * Handles URL: /profile/sharelog/fetch
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function fetch_share_log($request)
	{
		try {
			$paginate = $request->params()->query('paginate', null);

			$user = UserModel::getAuthUser();
			$data = ShareLogModel::getForUser($user->get('id'), $paginate)?->asArray();

			if (is_array($data)) {
				foreach ($data as $key => &$value) {
					$value['diffForHumans'] = (new Carbon($value['created_at']))->diffForHumans();
				}
			}

			return json([
				'code' => 200,
				'data' => $data
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}

	/**
	 * Handles URL: /profile/preferences
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function edit_preferences($request)
	{
		try {
			$validator = new Asatru\Controller\PostValidator([
				'name' => 'required|min:1',
				'email' => 'required|email',
				'lang' => 'required',
				'chatcolor' => 'required'
			]);

			if (!$validator->isValid()) {
				$errorstr = '';
				foreach ($validator->errorMsgs() as $err) {
					$errorstr .= $err . '<br/>';
				}

				FlashMessage::setMsg('error', 'Invalid data given:<br/>' . $errorstr);
				
				return back();
			}

			$name = $request->params()->query('name', null);
			$email = $request->params()->query('email', null);
			$lang = $request->params()->query('lang', 'en');
			$theme = $request->params()->query('theme', 'default');
			$chatcolor = $request->params()->query('chatcolor', null);
			$show_log = $request->params()->query('show_log', false);
			$show_calendar_view = $request->params()->query('show_calendar_view', false);
			$show_plant_id = $request->params()->query('show_plant_id', false);
			$notify_tasks_overdue = $request->params()->query('notify_tasks_overdue', false);
			$notify_tasks_tomorrow = $request->params()->query('notify_tasks_tomorrow', false);
			$notify_tasks_recurring = $request->params()->query('notify_tasks_recurring', false);
			$notify_calendar_reminder = $request->params()->query('notify_calendar_reminder', false);
			$notify_plant_care = $request->params()->query('notify_plant_care', false);
			$push_tasks_overdue = $request->params()->query('push_tasks_overdue', false);
			$push_tasks_tomorrow = $request->params()->query('push_tasks_tomorrow', false);
			$push_tasks_recurring = $request->params()->query('push_tasks_recurring', false);
			$push_calendar_reminder = $request->params()->query('push_calendar_reminder', false);
			$push_chat_message = $request->params()->query('push_chat_message', false);
			$push_plant_care = $request->params()->query('push_plant_care', false);
			$preferred_locations = $request->params()->query('preferred_locations', []);
			$show_plants_aoru = $request->params()->query('show_plants_aoru', 'added');
			$remember_location_sorting = $request->params()->query('remember_location_sorting', false);
			$weather_place = $request->params()->query('weather_place', null);
			$color_scheme = $request->params()->query('color_scheme', null);

			if ((is_numeric($theme)) && ((int)$theme === 0)) {
				$theme = null;
			}

			if ((is_numeric($weather_place)) && ((int)$weather_place === 0)) {
				$weather_place = null;
			}

			if ((is_numeric($color_scheme)) && ((int)$color_scheme === 0)) {
				$color_scheme = null;
			}

			if (!is_array($preferred_locations)) {
				$preferred_locations = [];
			}

			UserModel::editPreferences($name, $email, $lang, $theme, $chatcolor, $show_log, $show_calendar_view, $show_plant_id, $notify_tasks_overdue, $notify_tasks_tomorrow, $notify_tasks_recurring, $notify_calendar_reminder, $show_plants_aoru, $remember_location_sorting, $weather_place, $color_scheme, $notify_plant_care, $push_tasks_overdue, $push_tasks_tomorrow, $push_tasks_recurring, $push_calendar_reminder, $push_chat_message, $push_plant_care, $preferred_locations);

			UserModel::updateListSortingPreferences($remember_location_sorting);

			$password = $request->params()->query('password', null);
			if ($password) {
				$password_confirmation = $request->params()->query('password_confirmation', null);
				if ($password !== $password_confirmation) {
					throw new \Exception(__('app.password_mismatch'));
				}

				UserModel::updatePassword($password);
			}

			FlashMessage::setMsg('success', __('app.preferences_saved_successfully'));
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
		}

		return redirect('/profile');
	}

	/**
	 * Handles URL: /profile/notes/save
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function save_notes($request)
	{
		try {
			$notes = $request->params()->query('notes', null);

			UserModel::saveNotes($notes);

			FlashMessage::setMsg('success', __('app.personal_notes_saved_successfully'));
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
		}

		return redirect('/profile');
	}
}
