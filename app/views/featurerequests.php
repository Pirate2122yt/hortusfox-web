<h1>{{ __('app.feature_requests') }}</h1>

<div class="margin-vertical">
	<div class="action-strip action-strip-left">
		<div class="is-inline-block is-action-button-margin"><a class="button is-success" href="javascript:void(0);" onclick="window.vue.showAddFeatureRequest();">{{ __('app.add_feature_request') }}</a></div>

		<div class="is-inline-block is-action-button-margin"><a class="button is-link" href="{{ url('/feature-requests/changelog') }}"><i class="fas fa-list"></i>&nbsp;{{ __('app.view_changelog') }}</a></div>

		<div class="is-inline-block is-action-button-margin sorting-control select is-rounded is-small">
			<select onchange="location.href = '{{ url('/feature-requests') }}' + (this.value ? '?status=' + this.value : '');">
				<option value="">{{ __('app.feature_request_status_all') }}</option>
				@foreach ($statuses as $status_option)
					<option value="{{ $status_option }}" {{ (($status_filter) && ($status_filter === $status_option)) ? 'selected' : '' }}>{{ __('app.feature_request_status_' . $status_option) }}</option>
				@endforeach
			</select>
		</div>
	</div>
</div>

@include('flashmsg.php')

@if ((is_countable($requests)) && (count($requests) > 0))
<div class="plant-journal-entries" id="feature-request-entries">
	@foreach ($requests as $req)
		<div class="plant-journal-entry feature-request-entry" id="feature-request-entry-{{ $req->get('id') }}">
			<div class="plant-journal-entry-body">
				<div class="plant-journal-entry-header">
					<span class="plant-journal-entry-title" id="feature-request-title-{{ $req->get('id') }}" data-title="{{ $req->get('title') }}" data-description="{{ $req->get('description') }}">{{ $req->get('title') }}</span>
					<span class="feature-request-status-badge feature-request-status-{{ $req->get('status') }}">{{ __('app.feature_request_status_' . $req->get('status')) }}</span>
				</div>

				@if (strlen(trim($req->get('description') ?? '')) > 0)
					<div class="plant-journal-entry-content">{{ $req->get('description') }}</div>
				@endif

				<div class="plant-journal-entry-footer">
					<span class="plant-journal-entry-date">
						{{ __('app.feature_request_requested_by') }} {{ $requesters[$req->get('user')] ?? '?' }} &middot; {{ date('Y-m-d', strtotime($req->get('created_at'))) }}
					</span>

					<span class="plant-journal-entry-actions">
						@if (UserModel::isCurrentlyAdmin())
							<select class="feature-request-status-select" onchange="window.vue.changeFeatureRequestStatus('{{ $req->get('id') }}', this.value);">
								@foreach ($statuses as $status_option)
									<option value="{{ $status_option }}" {{ ($req->get('status') === $status_option) ? 'selected' : '' }}>{{ __('app.feature_request_status_' . $status_option) }}</option>
								@endforeach
							</select>&nbsp;
						@endif

						<a href="javascript:void(0);" class="feature-request-vote-btn {{ (($voted[$req->get('id')] ?? false)) ? 'is-voted' : '' }}" id="feature-request-vote-btn-{{ $req->get('id') }}" onclick="window.vue.voteFeatureRequest('{{ $req->get('id') }}');"><i class="fas fa-arrow-up"></i>&nbsp;<span id="feature-request-vote-count-{{ $req->get('id') }}">{{ $req->get('vote_count') }}</span></a>

						@if ((isset($user)) && (($req->get('user') == $user->get('id')) || (UserModel::isCurrentlyAdmin())))
							&nbsp;<a href="javascript:void(0);" onclick="let el = document.getElementById('feature-request-title-{{ $req->get('id') }}'); window.vue.showEditFeatureRequest('{{ $req->get('id') }}', el.dataset.title, el.dataset.description);"><i class="fas fa-edit is-color-darker"></i></a>&nbsp;<a href="javascript:void(0);" onclick="if (confirm('{{ __('app.confirm_remove_feature_request') }}')) { window.vue.removeFeatureRequest('{{ $req->get('id') }}'); }"><i class="fas fa-trash-alt is-color-darker"></i></a>
						@endif
					</span>
				</div>
			</div>
		</div>
	@endforeach
</div>
@else
	<strong>{{ __('app.no_feature_requests_yet') }}</strong>
@endif
