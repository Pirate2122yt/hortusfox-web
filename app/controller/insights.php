<?php

/**
 * Class InsightsController
 *
 * Gateway to the Insights dashboard: a read-only, aggregate view over
 * data the app already tracks per-plant (health state, care intervals,
 * when plants were added) but never rolled up across the whole
 * collection until now.
 */
class InsightsController extends BaseController {
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
	 * Handles URL: /insights
	 *
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\ViewHandler
	 */
	public function view_insights($request)
	{
		$user = UserModel::getAuthUser();

		$total_plants = count(PlantsModel::getAllPlants());
		$total_locations = LocationsModel::getCount();
		$warning_count = count(PlantsModel::getWarningPlants());
		$care_due_count = count(PlantsModel::getCareDuePlants());

		$health_breakdown = PlantsModel::getHealthStateBreakdown();

		$plants_by_location = [];
		foreach (LocationsModel::getAll() as $location_item) {
			$count = PlantsModel::getPlantCount($location_item->get('id'));
			if ($count > 0) {
				$plants_by_location[] = [
					'name' => $location_item->get('name'),
					'count' => $count
				];
			}
		}

		usort($plants_by_location, function ($a, $b) {
			return $b['count'] <=> $a['count'];
		});

		$plants_by_location = array_slice($plants_by_location, 0, 8);

		// Pre-computed here (rather than in the view) so the bar-width
		// percentages stay simple arithmetic in the view instead of
		// needing array_column()/max() calls there.
		$max_location_count = (count($plants_by_location) > 0) ? max(array_column($plants_by_location, 'count')) : 0;

		$growth_by_month = PlantsModel::getGrowthByMonth(12);
		$max_growth_count = max(1, max(array_column($growth_by_month, 'count')));

		return parent::view(['content', 'insights'], [
			'user' => $user,
			'total_plants' => $total_plants,
			'total_locations' => $total_locations,
			'warning_count' => $warning_count,
			'care_due_count' => $care_due_count,
			'health_breakdown' => $health_breakdown,
			'plants_by_location' => $plants_by_location,
			'max_location_count' => $max_location_count,
			'growth_by_month' => $growth_by_month,
			'max_growth_count' => $max_growth_count
		]);
	}
}
