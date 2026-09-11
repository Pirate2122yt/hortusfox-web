<h1>{{ __('app.changelog') }}</h1>

<h2 class="smaller-headline">{{ __('app.changelog_hint') }}</h2>

<div class="margin-vertical">
	<a class="is-default-link" href="{{ url('/feature-requests') }}"><i class="fas fa-arrow-left"></i>&nbsp;{{ __('app.back_to_feature_requests') }}</a>
</div>

@include('flashmsg.php')

@if ((is_countable($entries)) && (count($entries) > 0))
<div class="plant-journal-entries" id="changelog-entries">
	@foreach ($entries as $entry)
		<div class="plant-journal-entry" id="changelog-entry-{{ $entry->get('id') }}">
			<div class="plant-journal-entry-body">
				<div class="plant-journal-entry-header">
					<span class="plant-journal-entry-title">{{ $entry->get('title') }}</span>
					<span class="feature-request-status-badge feature-request-status-added">{{ __('app.feature_request_status_added') }}</span>
				</div>

				@if (strlen(trim($entry->get('description') ?? '')) > 0)
					<div class="plant-journal-entry-content">{{ $entry->get('description') }}</div>
				@endif

				<div class="plant-journal-entry-footer">
					<span class="plant-journal-entry-date">
						{{ __('app.feature_request_requested_by') }} {{ $requesters[$entry->get('user')] ?? '?' }} &middot; {{ __('app.changelog_added_on') }} {{ date('Y-m-d', strtotime($entry->get('updated_at'))) }}
					</span>
				</div>
			</div>
		</div>
	@endforeach
</div>
@else
	<strong>{{ __('app.no_changelog_entries_yet') }}</strong>
@endif
