<?php

/**
 * Class CalendarController
 * 
 * Gateway to the workspace calendar
 */
class CalendarController extends BaseController {
    const INDEX_LAYOUT = 'layout';

    /**
	 * Perform base initialization
	 * 
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct(self::INDEX_LAYOUT);

        if (!app('calendar_enable')) {
			throw new \Exception(403);
		}
	}

    /**
	 * Handles URL: /calendar
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\ViewHandler
	 */
	public function view_calendar($request)
	{
		$user = UserModel::getAuthUser();
		
		return parent::view(['content', 'calendar'], [
			'user' => $user
		]);
	}

    /**
	 * Handles URL: /calendar/query
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
    public function query_items($request)
    {
        try {
            $date_from = $request->params()->query('date_from', null);
            $date_till = $request->params()->query('date_till', null);

            if ($date_from === null) {
                $date_from = date('Y-m-d');
            }

            if ($date_till === null) {
                $date_till = date('Y-m-d', strtotime('+30 days'));
            }

            $items = CalendarModel::getItems($date_from, $date_till)->asArray();

            foreach ($items as $key => &$value) {
                if (isset($value['class_name'])) {
                    $value['class_descriptor'] = $value['class_name'];

                    $calendar_class_item = CalendarClassModel::findClass($value['class_name']);
                    if ($calendar_class_item) {
                        $value['class_name'] = __($calendar_class_item->get('name'));
                    } else {
                        $value['class_name'] = __('app.unknown_calendar_class');
                    }
                }
            }
            unset($value);

            // Auto-populate water/fertilise/repot due dates and one-off
            // milestones (currently: purchase date) from each plant's
            // own data - these aren't real CalendarModel rows, just
            // computed on the fly, so they're always in sync and show
            // up for however far in the past the queried range reaches.
            $care_class_cache = [];
            $auto_events = array_merge(
                PlantsModel::getCareCalendarEvents($date_from, $date_till),
                PlantsModel::getMilestoneCalendarEvents($date_from, $date_till)
            );

            foreach ($auto_events as $care_event) {
                $action = $care_event['action'];

                if (!array_key_exists($action, $care_class_cache)) {
                    $care_class_cache[$action] = CalendarClassModel::findClass($action);
                }
                $calendar_class_item = $care_class_cache[$action];

                $items[] = [
                    'id' => null,
                    'name' => $care_event['plant']->get('name'),
                    'date_from' => $care_event['due_date'] . ' 00:00:00',
                    'date_till' => $care_event['due_date'] . ' 00:00:00',
                    'class_descriptor' => $action,
                    'class_name' => ($calendar_class_item) ? __($calendar_class_item->get('name')) : __('app.unknown_calendar_class'),
                    'color_background' => ($calendar_class_item) ? $calendar_class_item->get('color_background') : 'rgb(150, 150, 150)',
                    'color_border' => ($calendar_class_item) ? $calendar_class_item->get('color_border') : 'rgb(200, 200, 200)',
                    'location' => $care_event['plant']->get('location'),
                    'is_care_event' => true,
                    'plant_id' => $care_event['plant']->get('id')
                ];
            }

            // Auto-populate real logged harvest dates too - these are
            // actual past events (HarvestLogModel), not a projection,
            // so they show up on the exact day they were logged for,
            // however far back that is.
            foreach (HarvestLogModel::getEntriesInRange($date_from, $date_till) as $harvest_entry) {
                $harvest_plant = PlantsModel::getDetails($harvest_entry->get('plant'));
                if ((!$harvest_plant) || ($harvest_plant->get('history')) || ($harvest_plant->get('deleted_at'))) {
                    continue;
                }

                if (!array_key_exists('harvest', $care_class_cache)) {
                    $care_class_cache['harvest'] = CalendarClassModel::findClass('harvest');
                }
                $calendar_class_item = $care_class_cache['harvest'];

                $items[] = [
                    'id' => null,
                    'name' => $harvest_plant->get('name'),
                    'date_from' => $harvest_entry->get('harvest_date') . ' 00:00:00',
                    'date_till' => $harvest_entry->get('harvest_date') . ' 00:00:00',
                    'class_descriptor' => 'harvest',
                    'class_name' => ($calendar_class_item) ? __($calendar_class_item->get('name')) : __('app.unknown_calendar_class'),
                    'color_background' => ($calendar_class_item) ? $calendar_class_item->get('color_background') : 'rgb(150, 150, 150)',
                    'color_border' => ($calendar_class_item) ? $calendar_class_item->get('color_border') : 'rgb(200, 200, 200)',
                    'location' => $harvest_plant->get('location'),
                    'is_care_event' => true,
                    'plant_id' => $harvest_plant->get('id')
                ];
            }

            return json([
                'code' => 200,
                'data' => $items,
                'date_from' => $date_from,
                'date_till' => $date_till
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => $e->getMessage()
            ]);
        }
    }

    /**
	 * Handles URL: /calendar/add
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
    public function add_item($request)
    {
        try {
            $validator = new Asatru\Controller\PostValidator([
                'name' => 'required',
                'date_from' => 'required',
                'date_till' => 'required'
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
            $date_from = $request->params()->query('date_from', null);
            $date_till = $request->params()->query('date_till', $date_from);
            $class = $request->params()->query('class', null);
            $location = $request->params()->query('location', null);

            CalendarModel::addItem($name, $date_from, $date_till, $class, false, $location);
    
            FlashMessage::setMsg('success', __('app.calendar_item_added'));
    
            return redirect('/calendar');
        } catch (\Exception $e) {
            FlashMessage::setMsg('error', $e->getMessage());
    
            return redirect('/calendar');
        }
    }

    /**
	 * Handles URL: /calendar/edit
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
    public function edit_item($request)
    {
        try {    
            $ident = $request->params()->query('ident', null);
            $name = $request->params()->query('name', null);
            $date_from = $request->params()->query('date_from', null);
            $date_till = $request->params()->query('date_till', $date_from);
            $class = $request->params()->query('class', null);
            $location = $request->params()->query('location', null);

            CalendarModel::editItem($ident, $name, $date_from, $date_till, $class, false, $location);
    
            FlashMessage::setMsg('success', __('app.calendar_item_edited'));
    
            return redirect('/calendar');
        } catch (\Exception $e) {
            FlashMessage::setMsg('error', $e->getMessage());
    
            return redirect('/calendar');
        }
    }

    /**
	 * Handles URL: /calendar/remove
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
    public function remove_item($request)
    {
        try {
            $ident = $request->params()->query('ident', null);

            CalendarModel::removeItem($ident);

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
