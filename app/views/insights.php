<h1>{{ __('app.insights') }}</h1>

<h2 class="smaller-headline">{{ __('app.insights_hint') }}</h2>

<div class="margin-vertical">
	<a class="is-default-link" href="{{ url('/') }}"><i class="fas fa-arrow-left"></i>&nbsp;{{ __('app.back_to_dashboard') }}</a>
</div>

@include('flashmsg.php')

<div class="insights-stat-grid">
	<div class="insights-stat-tile">
		<div class="insights-stat-value">{{ $total_plants }}</div>
		<div class="insights-stat-label">{{ __('app.insights_total_plants') }}</div>
	</div>

	<div class="insights-stat-tile">
		<div class="insights-stat-value">{{ $total_locations }}</div>
		<div class="insights-stat-label">{{ __('app.locations') }}</div>
	</div>

	<div class="insights-stat-tile">
		<div class="insights-stat-value">{{ $warning_count }}</div>
		<div class="insights-stat-label">{{ __('app.insights_plants_with_issues') }}</div>
	</div>

	<div class="insights-stat-tile">
		<div class="insights-stat-value">{{ $care_due_count }}</div>
		<div class="insights-stat-label">{{ __('app.insights_care_overdue') }}</div>
	</div>
</div>

<div class="settings-section">
	<h3 class="settings-section-title"><i class="far fa-heart"></i>&nbsp;{{ __('app.insights_health_breakdown') }}</h3>

	@if ($total_plants > 0)
		@foreach ($health_breakdown as $state => $count)
			<div class="insights-bar-row">
				<div class="insights-bar-label">{{ __('app.' . $state) }} ({{ $count }})</div>
				<div class="insights-bar-track">
					<div class="insights-bar-fill" style="width: {{ round($count / $total_plants * 100) }}%; background-color: {{ ($state === 'in_good_standing') ? 'rgb(115, 214, 103)' : 'rgb(212, 67, 67)' }};"></div>
				</div>
			</div>
		@endforeach
	@else
		<span class="is-not-available">{{ __('app.insights_no_data') }}</span>
	@endif
</div>

<div class="settings-section">
	<h3 class="settings-section-title"><i class="fas fa-map-marker-alt"></i>&nbsp;{{ __('app.insights_plants_by_location') }}</h3>

	@if ((is_countable($plants_by_location)) && (count($plants_by_location) > 0))
		@foreach ($plants_by_location as $location_row)
			<div class="insights-bar-row">
				<div class="insights-bar-label">{{ $location_row['name'] }} ({{ $location_row['count'] }})</div>
				<div class="insights-bar-track">
					<div class="insights-bar-fill" style="width: {{ ($max_location_count > 0) ? round($location_row['count'] / $max_location_count * 100) : 0 }}%; background-color: rgb(94, 145, 243);"></div>
				</div>
			</div>
		@endforeach
	@else
		<span class="is-not-available">{{ __('app.insights_no_data') }}</span>
	@endif
</div>

<div class="settings-section">
	<h3 class="settings-section-title"><i class="fas fa-seedling"></i>&nbsp;{{ __('app.insights_growth_title') }}</h3>

	<div class="insights-growth-chart">
		@foreach ($growth_by_month as $growth_row)
			<div class="insights-growth-column">
				<div class="insights-growth-bar-track">
					<div class="insights-growth-bar" style="height: {{ ($growth_row['count'] > 0) ? max(4, round($growth_row['count'] / $max_growth_count * 100)) : 0 }}%;" title="{{ $growth_row['count'] }}"></div>
				</div>
				<div class="insights-growth-label">{{ date('M', strtotime($growth_row['month'] . '-01')) }}</div>
			</div>
		@endforeach
	</div>
</div>
