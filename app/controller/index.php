<?php

/**
 * Class IndexController
 * 
 * Gateway to all main actions
 */
class IndexController extends BaseController {
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
	 * Handles URL: /
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\ViewHandler
	 */
	public function index($request)
	{
		$user = UserModel::getAuthUser();
		$locs = LocationsModel::getAll();

		$places = PlacesModel::getAll();

		$places_overview = [];
		foreach ($places as $place_item) {
			$place_locations = LocationsModel::getByPlace($place_item->get('id'));
			if ((!is_countable($place_locations)) || (count($place_locations) === 0)) {
				continue;
			}

			$plant_count = 0;
			$danger_count = 0;
			foreach ($place_locations as $place_location) {
				$plant_count += PlantsModel::getPlantCount($place_location->get('id'));
				$danger_count += PlantsModel::getDangerCount($place_location->get('id'));
			}

			$places_overview[] = [
				'id' => $place_item->get('id'),
				'name' => $place_item->get('name'),
				'icon' => $place_item->get('icon'),
				'location_count' => count($place_locations),
				'plant_count' => $plant_count,
				'danger_count' => $danger_count
			];
		}

		$unassigned_locations = LocationsModel::getUnassignedToPlace();

		$warning_plants = PlantsModel::getWarningPlants();
		$care_due_plants = PlantsModel::getCareDuePlants();
		$overdue_tasks = TasksModel::getOverdueTasks();
		$log = LogModel::getHistory();
		$stats = UtilsModule::getStats();

		$upcoming_tasks_overview = TasksModel::getTasks(false, 4);

		// Scope the homepage's reminder-ish widgets (warnings, care due,
		// overdue/upcoming tasks) to the user's preferred Locations, same
		// as their push notifications already are. A user with none
		// selected (the default) sees everything, unchanged from before.
		$preferred_location_ids = UserPreferredLocationModel::getLocationIdsForUser($user->get('id'));

		if (count($preferred_location_ids) > 0) {
			$plantLocationFilter = function($rows, $locationGetter) use ($preferred_location_ids) {
				$filtered = [];
				foreach ($rows as $row) {
					$locationId = $locationGetter($row);
					if (($locationId === null) || (in_array((int)$locationId, $preferred_location_ids, true))) {
						$filtered[] = $row;
					}
				}
				return $filtered;
			};

			$taskLocationGetter = function($task) {
				if (!PlantTasksRefModel::hasPlantReference($task->get('id'))) {
					return null;
				}

				$reference = PlantTasksRefModel::getForTask($task->get('id'));
				if (!$reference) {
					return null;
				}

				$plant = PlantsModel::getDetails($reference->get('plant_id'));
				return ($plant) ? $plant->get('location') : null;
			};

			$warning_plants = $plantLocationFilter($warning_plants, function($plant) { return $plant->get('location'); });
			$care_due_plants = $plantLocationFilter($care_due_plants, function($entry) { return $entry['plant']->get('location'); });
			$overdue_tasks = $plantLocationFilter($overdue_tasks, $taskLocationGetter);
			$upcoming_tasks_overview = $plantLocationFilter($upcoming_tasks_overview, $taskLocationGetter);
		}

		if ($user->get('show_plants_aoru')) {
			$last_plants_list = PlantsModel::getLastAddedPlants();
		} else {
			$last_plants_list = PlantsModel::getLastAuthoredPlants();
		}

		$weather = null;

		if (app('owm_enable')) {
			try {
				[$weather_lat, $weather_lon, $weather_place_id] = WeatherModule::resolveCoordinates($user);
				$weather = WeatherModule::today($weather_lat, $weather_lon, ($weather_place_id) ? ('weather_today_place_' . $weather_place_id) : 'weather_today');
			} catch (\Exception $e) {
				$weather = null;
			}
		}
		
		return parent::view(['content', 'index'], [
			'user' => $user,
			'warning_plants' => $warning_plants,
			'care_due_plants' => $care_due_plants,
			'overdue_tasks' => $overdue_tasks,
			'locations' => $locs,
			'places' => $places,
			'places_overview' => $places_overview,
			'unassigned_locations' => $unassigned_locations,
			'log' => $log,
			'stats' => $stats,
			'upcoming_tasks_overview' => $upcoming_tasks_overview,
			'last_plants_list' => $last_plants_list,
			'calendar_sv_date_from' => date('Y-m-d'),
			'calendar_sv_date_till' => date('Y-m-d', strtotime('+1 week')),
			'weather' => $weather
		]);
	}

	/**
	 * Handles URL: /auth
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\ViewHandler|Asatru\View\RedirectHandler
	 */
	public function auth($request)
	{
		if (auth()) {
			return redirect('/');
		}

		$view = new Asatru\View\ViewHandler();
		$view->setLayout('auth');

		return $view;
	}

	/**
	 * Handles URL: /login
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function login($request)
	{
		try {
			$email = $request->params()->query('email', null);
			$password = $request->params()->query('password', null);
			$redirect = $request->params()->query('redirect', null);

			$totp_required = UserModel::login($email, $password);

			if ($totp_required) {
				if ((is_string($redirect)) && (strlen($redirect) > 0)) {
					$_SESSION['pending_totp_redirect'] = $redirect;
				}

				return redirect('/login/2fa');
			}

			if ((is_string($redirect)) && (strlen($redirect) > 0)) {
				return redirect($redirect);
			}

			return redirect('/');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}

	/**
	 * Handles URL: /login/2fa
	 *
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\ViewHandler|Asatru\View\RedirectHandler
	 */
	public function view_totp_login($request)
	{
		if (auth()) {
			return redirect('/');
		}

		if (!isset($_SESSION['pending_totp_user'])) {
			return redirect('/auth');
		}

		$view = new Asatru\View\ViewHandler();
		$view->setLayout('auth_2fa');

		return $view;
	}

	/**
	 * Handles URL: /login/2fa
	 *
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function verify_totp($request)
	{
		try {
			$code = $request->params()->query('code', null);

			UserModel::verifyTotpLogin($code);

			$redirect = $_SESSION['pending_totp_redirect'] ?? null;
			unset($_SESSION['pending_totp_redirect']);

			if ((is_string($redirect)) && (strlen($redirect) > 0)) {
				return redirect($redirect);
			}

			return redirect('/');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return redirect('/login/2fa');
		}
	}

	/**
	 * Handles URL: /logout
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function logout($request)
	{
		try {
			UserModel::logout();

			return redirect('/');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}

	/**
	 * Handles URL: /password/restore
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function restore_password($request)
	{
		try {
			$email = $request->params()->query('email', null);

			UserModel::restorePassword($email);

			FlashMessage::setMsg('success', __('app.restore_password_info'));

			return redirect('/');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}

	/**
	 * Handles URL: /password/reset
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\ViewHandler
	 */
	public function view_reset_password($request)
	{
		$token = $request->params()->query('token');

		return view('pwreset', [], ['token' => $token]);
	}

	/**
	 * Handles URL: /password/reset
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function reset_password($request)
	{
		try {
			$token = $request->params()->query('token', null);
			$password = $request->params()->query('password', null);
			$password_confirmation = $request->params()->query('password_confirmation', null);

			if ($password !== $password_confirmation) {
				throw new \Exception(__('app.password_mismatch'));
			}

			UserModel::resetPassword($token, $password);

			return redirect('/');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}
}
