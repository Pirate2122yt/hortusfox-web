<h1>{{ __('app.plants') }}</h1>

<div class="margin-vertical">
	<div class="action-strip action-strip-left">
		<div class="is-inline-block is-action-button-margin"><a class="button is-success" href="javascript:void(0);" onclick="window.addNewPlant();">{{ __('app.add_plant') }}</a></div>
		<div class="is-inline-block is-action-button-margin"><a class="button" href="{{ url('/plants/export/csv') }}">{{ __('app.export_csv') }}</a></div>
		<form class="is-inline-block is-action-button-margin" method="POST" action="{{ url('/plants/import/csv') }}" enctype="multipart/form-data">
			@csrf
			<input type="file" name="csv" accept=".csv" required>
			<button type="submit" class="button">{{ __('app.import_csv') }}</button>
		</form>
		<div class="is-inline-block is-action-button-margin"><a class="is-default-link is-fixed-button-link is-fixed-margin-left-mobile" href="{{ url('/') }}">{{ __('app.back_to_dashboard') }}</a></div>
	</div>
</div>

@include('flashmsg.php')

<div class="sorting">
	<div class="sorting-control sorting-mobile-only">
		<a class="{{ ((((!isset($_GET['show'])) || ($_GET['show'] === 'cards')) && ((!isset($_COOKIE['list_show_style'])) || ($_COOKIE['list_show_style'] === 'cards'))) ? 'is-selected' : '') }}" href="{{ url('/plants?show=cards' . url_query('sorting', '&') . url_query('direction', '&')) }}">
			<i class="far fa-file-image"></i>
			<span>{{ __('app.plant_sorting_view_cards') }}</span>
		</a>
	</div>

	<div class="sorting-control sorting-mobile-only sorting-mobile-only-last-elem">
		<a class="{{ ((((isset($_GET['show'])) && ($_GET['show'] === 'list')) || ((isset($_COOKIE['list_show_style'])) && ($_COOKIE['list_show_style'] === 'list'))) ? 'is-selected' : '') }}" href="{{ url('/plants?show=list' . url_query('sorting', '&') . url_query('direction', '&')) }}">
			<i class="far fa-list-alt"></i>
			<span>{{ __('app.plant_sorting_view_list') }}</span>
		</a>
	</div>

	<div class="sorting-control select is-rounded is-small">
		<select onchange="location.href = '{{ url('/plants?sorting=') }}' + this.value + '{{ ((isset($list_order_style)) ? '&direction=' . $list_order_style : '') . url_query('show', '&') }}';">
			@foreach ($sorting_types as $sorting_type)
				@if (strpos($sorting_type, 'history') === false)
					<option value="{{ $sorting_type }}" {{ (($list_sorting_style) && ($list_sorting_style === $sorting_type)) ? 'selected' : '' }}>{{ __('app.sorting_type_' . $sorting_type) }}</option>
				@endif
			@endforeach
		</select>
	</div>

	<div class="sorting-control select is-rounded is-small">
		<select onchange="location.href = '{{ url('/plants?sorting=' . ((isset($list_sorting_style)) ? $list_sorting_style : 'name')) . url_query('show', '&') }}&direction=' + this.value;">
			@foreach ($sorting_dirs as $sorting_dir)
				<option value="{{ $sorting_dir }}" {{ ((isset($list_order_style)) && ($list_order_style === $sorting_dir)) ? 'selected' : '' }}>{{ __('app.sorting_dir_' . $sorting_dir) }}</option>
			@endforeach
		</select>
	</div>

	<div class="sorting-control is-rounded is-small">
		<input type="text" id="sorting-control-filter-text" placeholder="{{ __('app.filter_by_text') }}">
	</div>
</div>

<div class="plants">
	@if (count($plants) > 0)
		@foreach ($plants as $plant)
			@if (((!isset($_GET['show'])) || ($_GET['show'] === 'cards')) && ((!isset($_COOKIE['list_show_style'])) || ($_COOKIE['list_show_style'] === 'cards')))
				<a href="{{ url('/plants/details/' . $plant->get('id')) }}">
					<div class="plant-card plant-filter-text-root">
						<div class="plant-card-image" style="background-image: url('{{ abs_photo($plant->get('photo')) }}');">
							<div class="plant-card-overlay"></div>
						</div>

						@if ((isset($list_sorting_style)) && ($list_sorting_style !== 'name'))
							<div class="plant-card-sorting">{{ UtilsModule::readablePlantAttribute($plant->get($list_sorting_style), $list_sorting_style) }}</div>
						@endif

						<div class="plant-card-health-state">
							@if ($plant->get('health_state') !== 'in_good_standing')
								<i class="{{ PlantsModel::$plant_health_states[$plant->get('health_state')]['icon'] }} plant-state-{{ $plant->get('health_state') }}"></i>
							@endif
						</div>

						<div class="plant-card-title plant-card-title-with-hint plant-filter-text-target {{ ((strlen($plant->get('name')) > PlantsModel::PLANT_LONG_TEXT_THRESHOLD) ? 'plant-card-title-longtext' : '') }}">
							<div class="plant-card-title-first">
								@if ($user->get('show_plant_id'))
									<span class="plant-card-title-plant-id">{{ $plant->get('id') }}</span>
								@endif

								<span>{{ $plant->get('name') . (((PlantsModel::offspringCount($plant->get('id'))) || (PlantsModel::getDetails($plant->get('clone_origin')) !== null)) ? ' (' . strval($plant->get('clone_num') + 1) . ')' : '') }}</span>
							</div>

							<div class="plant-card-title-second"><i class="fas fa-map-marker-alt"></i>&nbsp;{{ LocationsModel::getNameById($plant->get('location')) }}</div>
						</div>
					</div>
				</a>
			@elseif (((isset($_GET['show'])) && ($_GET['show'] === 'list')) || ((isset($_COOKIE['list_show_style'])) && ($_COOKIE['list_show_style'] === 'list')))
				<a href="{{ url('/plants/details/' . $plant->get('id')) }}">
					<div class="plant-list-item plant-filter-text-root">
						<div class="plant-list-id">#{{ sprintf('%04d', $plant->get('id')) }}</div>
						<div class="plant-list-name-full plant-filter-text-target">{{ $plant->get('name') . (((PlantsModel::offspringCount($plant->get('id'))) || (PlantsModel::getDetails($plant->get('clone_origin')) !== null)) ? ' (' . strval($plant->get('clone_num') + 1) . ')' : '') }}</div>
						<div class="plant-list-name-short">{{ (((PlantsModel::offspringCount($plant->get('id'))) || (PlantsModel::getDetails($plant->get('clone_origin')) !== null)) ? '(' . strval($plant->get('clone_num') + 1) . ') ' : '') . substr($plant->get('name'), 0, PlantsModel::PLANT_LIST_MAX_STRLEN) . '...' }}</div>
						<div class="plant-list-scientific-name plant-list-item-hide-small-devices">{{ ($plant->get('scientific_name') ?? 'N/A') }}</div>
						<div class="plant-list-location plant-list-item-hide-small-devices">{{ LocationsModel::getNameById($plant->get('location')) }}</div>
						@if ((isset($list_sorting_style)) && ($list_sorting_style !== 'name'))
							<div class="plant-list-sorting">{{ UtilsModule::readablePlantAttribute($plant->get($list_sorting_style), $list_sorting_style) }}</div>
						@else
							@if ($plant->get('last_edited_date'))
							<div class="plant-list-last-edited">{{ (new Carbon($plant->get('last_edited_date')))->diffForHumans() }}</div>
							@endif
						@endif
					</div>
				</a>
			@endif
		@endforeach
	@else
		<div class="plants-empty">
			<div class="plants-empty-image">
				<img src="{{ asset('img/plantsempty.png') }}" alt="image"/>
			</div>

			<div class="plants-empty-text">{{ __('app.content_empty') }}</div>
		</div>
	@endif
</div>
