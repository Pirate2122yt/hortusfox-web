<h1>{{ __('app.changelog') }}</h1>

<h2 class="smaller-headline">{{ __('app.changelog_hint') }}</h2>

<div class="margin-vertical">
	<a class="is-default-link" href="{{ url('/feature-requests') }}"><i class="fas fa-arrow-left"></i>&nbsp;{{ __('app.back_to_feature_requests') }}</a>
</div>

@include('flashmsg.php')

@if (UserModel::isCurrentlyAdmin())
	<div class="margin-vertical">
		<a class="button is-success" href="javascript:void(0);" onclick="window.showAddChangelogEntry();">{{ __('app.add_changelog_entry') }}</a>
	</div>
@endif

@if ((is_countable($entries)) && (count($entries) > 0))
<div class="plant-journal-entries" id="changelog-entries">
	@foreach ($entries as $entry)
		<div class="plant-journal-entry" id="changelog-entry-{{ $entry->get('id') }}">
			<div class="plant-journal-entry-body">
				<div class="plant-journal-entry-header">
					<span class="plant-journal-entry-title" id="changelog-title-{{ $entry->get('id') }}"
						data-title="{{ $entry->get('title') }}"
						data-description="{{ $entry->get('description') }}"
						data-entry_date="{{ $entry->get('entry_date') }}">{{ $entry->get('title') }}</span>
				</div>

				@if (strlen(trim($entry->get('description') ?? '')) > 0)
					<div class="plant-journal-entry-content">{{ $entry->get('description') }}</div>
				@endif

				<div class="plant-journal-entry-footer">
					<span class="plant-journal-entry-date">
						{{ __('app.changelog_added_on') }} {{ date('Y-m-d', strtotime($entry->get('entry_date'))) }}
					</span>

					@if (UserModel::isCurrentlyAdmin())
						<span class="plant-journal-entry-actions">
							<a href="javascript:void(0);" title="{{ __('app.edit') }}" onclick="let el = document.getElementById('changelog-title-{{ $entry->get('id') }}'); window.showEditChangelogEntry('{{ $entry->get('id') }}', el.dataset);"><i class="fas fa-edit is-color-darker"></i></a>&nbsp;<a href="javascript:void(0);" title="{{ __('app.remove') }}" onclick="if (confirm(window.CHANGELOG_REMOVE_CONFIRM)) { document.getElementById('frmRemoveChangelogEntry-{{ $entry->get('id') }}').submit(); }"><i class="fas fa-trash-alt is-color-darker"></i></a>

							<form id="frmRemoveChangelogEntry-{{ $entry->get('id') }}" method="POST" action="{{ url('/feature-requests/changelog/remove') }}" class="is-hidden">
								@csrf
								<input type="hidden" name="item" value="{{ $entry->get('id') }}">
							</form>
						</span>
					@endif
				</div>
			</div>
		</div>
	@endforeach
</div>
@else
	<strong>{{ __('app.no_changelog_entries_yet') }}</strong>
@endif

@if (UserModel::isCurrentlyAdmin())
	<div class="modal" id="changelogAddModal">
		<div class="modal-background" onclick="window.hideChangelogModals();"></div>
		<div class="modal-card">
			<header class="modal-card-head is-stretched">
				<p class="modal-card-title">{{ __('app.add_changelog_entry') }}</p>
				<button class="delete" aria-label="close" onclick="window.hideChangelogModals();"></button>
			</header>
			<section class="modal-card-body is-stretched">
				<form id="frmAddChangelogEntry" method="POST" action="{{ url('/feature-requests/changelog/add') }}">
					@csrf

					<div class="field">
						<label class="label">{{ __('app.changelog_entry_title_label') }}</label>
						<div class="control">
							<input type="text" class="input" name="title" required>
						</div>
					</div>

					<div class="field">
						<label class="label">{{ __('app.changelog_entry_date_label') }}</label>
						<div class="control">
							<input type="date" class="input" name="entry_date" id="inpAddChangelogDate" required>
						</div>
					</div>

					<div class="field">
						<label class="label">{{ __('app.changelog_entry_description_label') }}</label>
						<div class="control">
							<textarea class="textarea" name="description"></textarea>
						</div>
					</div>
				</form>
			</section>
			<footer class="modal-card-foot is-stretched">
				<button class="button is-success" onclick="document.getElementById('frmAddChangelogEntry').submit();">{{ __('app.add') }}</button>
				<button class="button" onclick="window.hideChangelogModals();">{{ __('app.cancel') }}</button>
			</footer>
		</div>
	</div>

	<div class="modal" id="changelogEditModal">
		<div class="modal-background" onclick="window.hideChangelogModals();"></div>
		<div class="modal-card">
			<header class="modal-card-head is-stretched">
				<p class="modal-card-title">{{ __('app.edit_changelog_entry') }}</p>
				<button class="delete" aria-label="close" onclick="window.hideChangelogModals();"></button>
			</header>
			<section class="modal-card-body is-stretched">
				<form id="frmEditChangelogEntry" method="POST" action="{{ url('/feature-requests/changelog/edit') }}">
					@csrf
					<input type="hidden" name="item" id="inpEditChangelogItemId">

					<div class="field">
						<label class="label">{{ __('app.changelog_entry_title_label') }}</label>
						<div class="control">
							<input type="text" class="input" name="title" id="inpEditChangelogTitle" required>
						</div>
					</div>

					<div class="field">
						<label class="label">{{ __('app.changelog_entry_date_label') }}</label>
						<div class="control">
							<input type="date" class="input" name="entry_date" id="inpEditChangelogDate" required>
						</div>
					</div>

					<div class="field">
						<label class="label">{{ __('app.changelog_entry_description_label') }}</label>
						<div class="control">
							<textarea class="textarea" name="description" id="inpEditChangelogDescription"></textarea>
						</div>
					</div>
				</form>
			</section>
			<footer class="modal-card-foot is-stretched">
				<button class="button is-success" onclick="document.getElementById('frmEditChangelogEntry').submit();">{{ __('app.save') }}</button>
				<button class="button" onclick="window.hideChangelogModals();">{{ __('app.cancel') }}</button>
			</footer>
		</div>
	</div>

	<script>
		// json_encode (not a plain double-curly interpolation) so an
		// apostrophe in the translated text can't break out of the
		// single-quoted JS string it's used in below. Same reasoning as
		// chat.php's CHAT_DELETE_CONFIRM and wishlist.php's
		// WISHLIST_REMOVE_CONFIRM.
		window.CHANGELOG_REMOVE_CONFIRM = {!! json_encode(__('app.confirm_remove_changelog_entry')) !!};

		window.showAddChangelogEntry = function() {
			document.getElementById('frmAddChangelogEntry').reset();
			document.getElementById('inpAddChangelogDate').value = new Date().toISOString().slice(0, 10);
			document.getElementById('changelogAddModal').classList.add('is-active');
		};

		window.showEditChangelogEntry = function(id, data) {
			document.getElementById('inpEditChangelogItemId').value = id;
			document.getElementById('inpEditChangelogTitle').value = data.title || '';
			document.getElementById('inpEditChangelogDescription').value = data.description || '';
			document.getElementById('inpEditChangelogDate').value = data.entry_date || '';

			document.getElementById('changelogEditModal').classList.add('is-active');
		};

		window.hideChangelogModals = function() {
			document.getElementById('changelogAddModal').classList.remove('is-active');
			document.getElementById('changelogEditModal').classList.remove('is-active');
		};
	</script>
@endif
