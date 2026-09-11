<h1>{{ $location_data->get('name') }}</h1>

<div class="margin-vertical">
	<div class="action-strip action-strip-left">
		<div class="is-inline-block is-action-button-margin"><a class="button is-success" href="javascript:void(0);" onclick="window.addNewPlant();">{{ __('app.add_plant') }}</a></div>
		@if (plant_attr('last_watered'))
		<div class="is-inline-block is-action-button-margin"><a class="button is-info" href="javascript:void(0);" onclick="window.vue.showPerformBulkUpdate('last_watered', '{{ __('app.bulk_set_watered') }}', '{{ __('app.set_watered') }}', '{{ $location }}');">{{ __('app.set_watered') }}</a></div>
		@endif
		@if (plant_attr('last_repotted'))
		<div class="is-inline-block is-action-button-margin"><a class="button is-warning" href="javascript:void(0);" onclick="window.vue.showPerformBulkUpdate('last_repotted', '{{ __('app.bulk_set_repotted') }}', '{{ __('app.set_repotted') }}', '{{ $location }}');">{{ __('app.set_repotted') }}</a></div>
		@endif
		@if (plant_attr('last_fertilised'))
		<div class="is-inline-block is-action-button-margin"><a class="button is-chocolate" href="javascript:void(0);" onclick="window.vue.showPerformBulkUpdate('last_fertilised', '{{ __('app.bulk_set_fertilised') }}', '{{ __('app.set_fertilised') }}', '{{ $location }}');">{{ __('app.set_fertilised') }}</a></div>
		@endif
		@foreach (CustBulkCmdModel::getCmdList() as $bulk_cmd)
		<div class="is-inline-block is-action-button-margin"><a class="button" style="{{ $bulk_cmd->get('styles') }}" href="javascript:void(0);" onclick="window.vue.showPerformBulkUpdate('{{ $bulk_cmd->get('attribute') }}', '{{ $bulk_cmd->get('label') }}', '{{ $bulk_cmd->get('label') }}', '{{ $location }}', true, '{{ $bulk_cmd->get('datatype') }}');">{{ $bulk_cmd->get('label') }}</a></div>
		@endforeach
		<div class="is-inline-block is-action-button-margin"><a class="button" href="javascript:void(0);" onclick="window.vue.bShowPlantBulkPrint = true;">{{ __('app.bulk_print_qr_codes') }}</a></div>
		<div class="is-inline-block is-action-button-margin"><a class="button" href="javascript:void(0);" onclick="window.vue.showPerformBulkUpdate('location', '{{ __('app.bulk_move_plants') }}', '{{ __('app.move_plants') }}', '{{ $location }}', false, 'string'); window.vue.setBulkComboValues(window.locationList);">{{ __('app.move_plants') }}</a></div>
		<div class="is-inline-block is-action-button-margin"><a class="button" href="{{ url('/plants/trash') }}"><i class="fas fa-trash-restore"></i>&nbsp;{{ __('app.recycle_bin') }}</a></div>
		<div class="is-inline-block is-action-button-margin"><a class="is-default-link is-fixed-button-link is-fixed-margin-left-mobile" href="{{ url('/') }}">{{ __('app.back_to_dashboard') }}</a></div>
	</div>

	<div class="action-strip action-strip-right">
		<div class="is-inline-block is-action-button-margin float-right"><a class="is-gray-link" href="javascript:void(0);" onclick="document.querySelector('#location-log-anchor').scrollIntoView({behavior: 'smooth'});"><i class="far fa-file-alt fa-lg"></i></a></div>
		<div class="is-inline-block is-action-button-margin float-right"><a class="is-gray-link" href="javascript:void(0);" onclick="document.querySelector('#location-notes-anchor').scrollIntoView({behavior: 'smooth'});"><i class="fas fa-align-center fa-lg"></i></a></div>
	</div>
</div>

@include('flashmsg.php')

<div class="sorting">
	<div class="sorting-control sorting-mobile-only">
		<a class="{{ ((((!isset($_GET['show'])) || ($_GET['show'] === 'cards')) && ((!isset($_COOKIE['list_show_style'])) || ($_COOKIE['list_show_style'] === 'cards'))) ? 'is-selected' : '') }}" href="{{ url('/plants/location/' . $location . '?show=cards' . url_query('sorting', '&') . url_query('direction', '&')) }}">
			<i class="far fa-file-image"></i>
			<span>{{ __('app.plant_sorting_view_cards') }}</span>
		</a>
	</div>

	<div class="sorting-control sorting-mobile-only sorting-mobile-only-last-elem">
		<a class="{{ ((((isset($_GET['show'])) && ($_GET['show'] === 'list')) || ((isset($_COOKIE['list_show_style'])) && ($_COOKIE['list_show_style'] === 'list'))) ? 'is-selected' : '') }}" href="{{ url('/plants/location/' . $location . '?show=list' . url_query('sorting', '&') . url_query('direction', '&')) }}">
			<i class="far fa-list-alt"></i>
			<span>{{ __('app.plant_sorting_view_list') }}</span>
		</a>
	</div>

	<div class="sorting-control select is-rounded is-small">
		<select onchange="location.href = '{{ url('/plants/location/' . $location . '?sorting=') }}' + this.value + '{{ ((isset($list_order_style)) ? '&direction=' . $list_order_style : '') . url_query('show', '&') }}';">
			@foreach ($sorting_types as $sorting_type)
				@if (strpos($sorting_type, 'history') === false)
					<option value="{{ $sorting_type }}" {{ (($list_sorting_style) && ($list_sorting_style === $sorting_type)) ? 'selected' : '' }}>{{ __('app.sorting_type_' . $sorting_type) }}</option>
				@endif
			@endforeach
		</select>
	</div>

	<div class="sorting-control select is-rounded is-small">
		<select onchange="location.href = '{{ url('/plants/location/' . $location . '?sorting=' . ((isset($list_sorting_style)) ? $list_sorting_style : 'name')) . url_query('show', '&') }}&direction=' + this.value;">
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

						<div class="plant-card-title plant-filter-text-target {{ ((strlen($plant->get('name')) > PlantsModel::PLANT_LONG_TEXT_THRESHOLD) ? 'plant-card-title-longtext' : '') }}">
							@if ($user->get('show_plant_id'))
								<span class="plant-card-title-plant-id">{{ $plant->get('id') }}</span>
							@endif

							<span>{{ $plant->get('name') . (((PlantsModel::offspringCount($plant->get('id'))) || (PlantsModel::getDetails($plant->get('clone_origin')) !== null)) ? ' (' . strval($plant->get('clone_num') + 1) . ')' : '') }}</span>
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

<div class="is-dark-delimiter"><hr/></div>

<div class="location-notes">
	<div class="location-notes-title">{{ __('app.notes') }}</div>

	<a name="location-notes-anchor" id="location-notes-anchor"></a>

	<div id="location-notes-edit">
		<div><textarea id="location-notes-content" class="textarea" oninput="document.getElementById('location-notes-result').innerHTML = '';">{{ $location_data->get('notes') ?? '' }}</textarea></div>
		<div><a class="button is-success" href="javascript:void(0);" onclick="window.vue.saveLocationNotes('{{ $location_data->get('id') }}', 'location-notes-content', 'location-notes-result');">{{ __('app.save') }}</a>&nbsp;<span id="location-notes-result"></span></div>
	</div>
</div>

<div class="is-dark-delimiter"><hr/></div>

<div class="plant-journal location-log">
	<div class="plant-journal-title location-log-title">{{ __('app.location_log') }}</div>

	<a name="location-log-anchor" id="location-log-anchor"></a>

	<div class="plant-journal-add">
		<a class="button is-info" id="location-journal-add-btn" data-plants="{{ $location_plants_json }}" href="javascript:void(0);" onclick="window.vue.showAddLocationLogEntry('{{ $location }}', 'location-journal-anchor');">{{ __('app.add_location_log_entry') }}</a>

		<label class="checkbox plant-journal-system-toggle">
			<input type="checkbox" id="location-journal-toggle-system" onchange="window.vue.toggleLocationJournalSystemEntries(this.checked);">
			{{ __('app.plant_journal_show_system_label') }}
		</label>
	</div>

	@if ((is_countable($location_log_entries)) && (count($location_log_entries) > 0))
	<div class="plant-journal-entries" id="location-journal-entries">
		@foreach ($location_log_entries as $location_log_entry)
			<div class="plant-journal-entry{{ $location_log_entry->get('is_system') ? ' is-system' : '' }}" id="location-log-entry-table-row-{{ $location_log_entry->get('id') }}">
				@if (isset($location_log_entry_photos[$location_log_entry->get('id')]) && count($location_log_entry_photos[$location_log_entry->get('id')]) > 0)
					<div class="plant-journal-entry-photos">
						@foreach ($location_log_entry_photos[$location_log_entry->get('id')] as $entry_photo)
							<a href="{{ abs_photo($entry_photo['original']) }}" target="_blank" class="plant-journal-entry-photo">
								<img src="{{ abs_photo($entry_photo['thumb']) }}" alt="photo"/>
							</a>
						@endforeach
					</div>
				@endif

				<div class="plant-journal-entry-body">
					<div class="plant-journal-entry-header">
						<span class="plant-journal-entry-title" id="location-log-entry-item-{{ $location_log_entry->get('id') }}" data-title="{{ $location_log_entry->get('title') }}" data-content="{{ $location_log_entry->get('content') }}" data-tags="{{ $location_log_entry->get('tags') }}" data-entry-date="{{ $location_log_entry->get('entry_date') ? date('Y-m-d', strtotime($location_log_entry->get('entry_date'))) : date('Y-m-d', strtotime($location_log_entry->get('created_at'))) }}" data-photos="{{ json_encode($location_log_entry_photos[$location_log_entry->get('id')] ?? []) }}" data-plants="{{ json_encode($location_log_entry_plants[$location_log_entry->get('id')] ?? []) }}">{{ $location_log_entry->get('title') }}</span>
						@if ($location_log_entry->get('is_system'))
							<span class="plant-journal-entry-system-badge">{{ __('app.plant_journal_system_badge') }}</span>
						@endif
					</div>

					@if (strlen(trim($location_log_entry->get('content') ?? '')) > 0)
						<div class="plant-journal-entry-content">{{ $location_log_entry->get('content') }}</div>
					@endif

					@if (strlen(trim($location_log_entry->get('tags') ?? '')) > 0)
						<div class="plant-journal-entry-tags">
							@foreach (preg_split('/\s+/', trim($location_log_entry->get('tags'))) as $entry_tag)
								@if (strlen($entry_tag) > 0)
									<span class="plant-journal-entry-tag">{{ $entry_tag }}</span>
								@endif
							@endforeach
						</div>
					@endif

					@if (isset($location_log_entry_plant_names[$location_log_entry->get('id')]) && count($location_log_entry_plant_names[$location_log_entry->get('id')]) > 0)
						<div class="plant-journal-entry-tags">
							@foreach ($location_log_entry_plant_names[$location_log_entry->get('id')] as $entry_plant_name)
								<span class="plant-journal-entry-plant-chip"><i class="fas fa-seedling"></i>&nbsp;{{ $entry_plant_name }}</span>
							@endforeach
						</div>
					@endif

					<div class="plant-journal-entry-footer">
						<span class="plant-journal-entry-date">{{ $location_log_entry->get('entry_date') ? date('Y-m-d', strtotime($location_log_entry->get('entry_date'))) : date('Y-m-d', strtotime($location_log_entry->get('created_at'))) }}</span>
						<span class="plant-journal-entry-actions">
							<a href="javascript:void(0);" onclick="let el = document.getElementById('location-log-entry-item-{{ $location_log_entry->get('id') }}'); window.vue.showEditLocationLogEntry('{{ $location_log_entry->get('id') }}', '{{ $location_log_entry->get('location') }}', el.dataset.title, el.dataset.content, el.dataset.tags, el.dataset.entryDate, JSON.parse(el.dataset.photos), JSON.parse(el.dataset.plants), 'location-journal-anchor');"><i class="fas fa-edit is-color-darker"></i></a>&nbsp;<a href="javascript:void(0);" onclick="if (confirm('{{ __('app.confirm_remove_location_log_entry') }}')) { window.vue.removeLocationLogEntry('{{ $location_log_entry->get('id') }}', 'location-log-entry-table-row-{{ $location_log_entry->get('id') }}'); }"><i class="fas fa-trash-alt is-color-darker"></i></a>
						</span>
					</div>
				</div>
			</div>
		@endforeach

		@if ($location_log_entries->get(count($location_log_entries) - 1)?->get('id') > 1)
			<div id="location-log-load-more" class="plant-journal-paginate">
				<a href="javascript:void(0);" onclick="window.vue.loadNextLocationLogEntries(this, '{{ $location }}', document.getElementById('location-journal-entries'));" data-paginate="{{ $location_log_entries->get(count($location_log_entries) - 1)?->get('id') }}" data-paginate-date="{{ $location_log_entries->get(count($location_log_entries) - 1)?->get('entry_date') ? date('Y-m-d', strtotime($location_log_entries->get(count($location_log_entries) - 1)?->get('entry_date'))) : date('Y-m-d', strtotime($location_log_entries->get(count($location_log_entries) - 1)?->get('created_at'))) }}">{{ __('app.load_more') }}</a>
			</div>
		@endif
	</div>
	@else
		<strong>{{ __('app.no_location_log_entries_yet') }}</strong>
	@endif
</div>

<a name="location-journal-anchor" id="location-journal-anchor"></a>
