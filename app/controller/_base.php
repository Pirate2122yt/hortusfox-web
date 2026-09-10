<?php 

/**
 * Class BaseController
 * 
 * Perform base operations that are the same for various child controllers
 */
class BaseController extends Asatru\Controller\Controller {
	/**
	 * @var string
	 */
	protected $layout = 'layout';

	/**
	 * Perform base initialization
	 * 
	 * @param $layout
	 * @return void
	 */
	public function __construct($layout = '')
	{
		if ($layout !== '') {
			$this->layout = $layout;
		}

		app_mail_config();
		app_set_timezone();

		if (app('auth_proxy_enable')) {
			try {
				UserModel::performProxyAuth();
			} catch (\Exception $e) {
				http_response_code(401);
				exit($e->getMessage());
			}
		}

		$auth_user = UserModel::getAuthUser();
		if (!$auth_user) {
			$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

			$allowed_urls = array(
				'/auth',
				'/login',
				'/password/restore',
				'/password/reset',
				'/cronjob/tasks/overdue',
				'/cronjob/tasks/tomorrow',
				'/cronjob/calendar/reminder',
				'/cronjob/backup/auto'
			);

			// The public, read-only plant catalogue (see PublicController)
			// is reachable without a session, same as the URLs above -
			// unless an admin has switched it off entirely, in which case
			// anonymous visitors are bounced to the login screen like any
			// other page.
			$is_public_url = (strpos($url, '/public') === 0) && (app('public_catalog_enable', true));

			if ((!in_array($url, $allowed_urls)) && (!$is_public_url)) {
				header('Location: /auth?redirect=' . urlencode($_SERVER['REQUEST_URI']));
				exit();
			}
		} else {
			UserModel::updateOnlineStatus();

			if ((is_string($auth_user->get('lang'))) && (strlen($auth_user->get('lang')) > 0)) {
				UtilsModule::setLanguage($auth_user->get('lang'));
			} else {
				$lang = app('language', env('APP_LANG', 'en'));
				if (($lang !== null) && (is_string($lang))) {
					UtilsModule::setLanguage($lang);
				}
			}

			$theme = $auth_user->get('theme');

			if (($theme) && (is_dir(public_path() . '/themes/' . $theme))) {
				ThemeModule::load(public_path() . '/themes/' . $theme);
			}
		}
	}

	/**
	 * Query URL parameters
	 * 
	 * @param $name
	 * @param $fallback
	 * @return mixed
	 */
	public function param($name, $fallback = null)
	{
		if (isset($_GET[$name])) {
			return $_GET[$name];
		} else if (isset($_POST[$name])) {
			return $_POST[$name];
		}

		return $fallback;
	}

	/**
	 * A more convenient view helper
	 *
	 * @param array $yields
	 * @param array $attr
	 * @return Asatru\View\ViewHandler
	 */
	public function view($yields, $attr = array())
	{
		// The shared layout's preferences modal (edit-preferences form) lets
		// a user pick a default weather location, so make sure $locations
		// is always available to it regardless of which controller renders
		// the layout, unless the controller already supplied its own list.
		if ((!isset($attr['locations'])) && ($this->layout === 'layout')) {
			$attr['locations'] = LocationsModel::getAll();
		}

		// Same for $places - the preferences modal's default weather Place
		// picker needs the full list regardless of which controller renders
		// the layout, unless one was already supplied.
		if ((!isset($attr['places'])) && ($this->layout === 'layout')) {
			$attr['places'] = PlacesModel::getAll();
		}

		return view($this->layout, $yields, $attr);
	}
}