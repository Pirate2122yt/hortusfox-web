<h1>{{ __('app.wishlist') }}</h1>

<div class="margin-vertical">
	<div class="action-strip action-strip-left">
		<div class="is-inline-block is-action-button-margin"><a class="button is-success" href="javascript:void(0);" onclick="window.showAddWishlistItem();">{{ __('app.add_wishlist_item') }}</a></div>

		<div class="is-inline-block is-action-button-margin sorting-control select is-rounded is-small">
			<select onchange="location.href = this.value;">
				<option value="{{ url('/wishlist?view=mine') }}" {{ ($view === 'mine') ? 'selected' : '' }}>{{ __('app.wishlist_view_mine') }}</option>
				<option value="{{ url('/wishlist?view=overall') }}" {{ ($view === 'overall') ? 'selected' : '' }}>{{ __('app.wishlist_view_overall') }}</option>
				<option value="{{ url('/wishlist?view=place') }}" {{ ($view === 'place') ? 'selected' : '' }}>{{ __('app.wishlist_view_place') }}</option>
			</select>
		</div>
	</div>
</div>

@include('flashmsg.php')

@if ($total_price > 0)
	<p class="margin-vertical"><strong>{{ __('app.wishlist_total_price') }}:</strong>&nbsp;{{ number_format($total_price, 2) }}</p>
@endif

<div class="margin-vertical">
	@if ($share_url)
		<div class="field has-addons">
			<div class="control is-expanded">
				<input type="text" class="input" id="wishlistShareUrl" value="{{ $share_url }}" readonly onclick="this.select();">
			</div>
			<div class="control">
				<a class="button" href="javascript:void(0);" onclick="window.copyWishlistShareLink();">{{ __('app.copy') }}</a>
			</div>
		</div>

		<form method="POST" action="{{ url('/wishlist/share/toggle') }}" class="is-inline-block">
			@csrf
			<input type="hidden" name="enable" value="0">
			<button type="submit" class="button is-small">{{ __('app.wishlist_share_disable') }}</button>
		</form>&nbsp;
		<form method="POST" action="{{ url('/wishlist/share/regenerate') }}" class="is-inline-block" onsubmit="return confirm('{{ __('app.confirm_wishlist_share_regenerate') }}');">
			@csrf
			<button type="submit" class="button is-small">{{ __('app.wishlist_share_regenerate') }}</button>
		</form>

		<p class="help">{{ __('app.wishlist_share_enabled_hint') }}</p>
	@else
		<form method="POST" action="{{ url('/wishlist/share/toggle') }}">
			@csrf
			<input type="hidden" name="enable" value="1">
			<button type="submit" class="button is-small">{{ __('app.wishlist_share_enable') }}</button>
		</form>
		<p class="help">{{ __('app.wishlist_share_disabled_hint') }}</p>
	@endif
</div>

@if ($view === 'place')
	@if (count($places_grouped) > 0)
		@foreach ($places_grouped as $place_group)
			<h2 class="subtitle margin-vertical">
				@if ($place_group['place'])
					{{ $place_group['place']->get('name') }}
				@else
					{{ __('app.wishlist_no_place') }}
				@endif
			</h2>

			<div class="plant-journal-entries">
				@foreach ($place_group['items'] as $wishlist_item)
					@include('wishlist_entry.php')
				@endforeach
			</div>
		@endforeach
	@else
		<strong>{{ __('app.wishlist_empty') }}</strong>
	@endif
@else
	@if ((is_countable($items)) && (count($items) > 0))
		<div class="plant-journal-entries" id="wishlist-entries">
			@foreach ($items as $wishlist_item)
				@include('wishlist_entry.php')
			@endforeach
		</div>
	@else
		<strong>{{ __('app.wishlist_empty') }}</strong>
	@endif
@endif

<div class="modal" id="wishlistAddModal">
	<div class="modal-background" onclick="window.hideWishlistModals();"></div>
	<div class="modal-card">
		<header class="modal-card-head is-stretched">
			<p class="modal-card-title">{{ __('app.add_wishlist_item') }}</p>
			<button class="delete" aria-label="close" onclick="window.hideWishlistModals();"></button>
		</header>
		<section class="modal-card-body is-stretched">
			<form id="frmAddWishlistItem" method="POST" action="{{ url('/wishlist/add') }}" enctype="multipart/form-data">
				@csrf

				<div class="field">
					<label class="label">{{ __('app.name') }}</label>
					<div class="control">
						<input type="text" class="input" name="name" required>
					</div>
				</div>

				<div class="field">
					<label class="label">{{ __('app.wishlist_species') }}</label>
					<div class="control">
						<input type="text" class="input" name="species">
					</div>
				</div>

				<div class="field">
					<label class="label">{{ __('app.wishlist_cultivar') }}</label>
					<div class="control">
						<input type="text" class="input" name="cultivar">
					</div>
				</div>

				<div class="field">
					<label class="label">{{ __('app.wishlist_priority') }}</label>
					<div class="control">
						<select class="input" name="priority">
							@foreach ($priorities as $priority_option)
								<option value="{{ $priority_option }}" {{ ($priority_option === 'would_like') ? 'selected' : '' }}>{{ __('app.wishlist_priority_' . $priority_option) }}</option>
							@endforeach
						</select>
					</div>
				</div>

				<div class="field">
					<label class="label">{{ __('app.location') }}</label>
					<div class="control">
						<select class="input" name="location">
							<option value="">{{ __('app.wishlist_no_location') }}</option>
							@foreach ($locations as $loc)
								<option value="{{ $loc->get('id') }}">{{ $loc->get('name') }}</option>
							@endforeach
						</select>
					</div>
				</div>

				<div class="field">
					<label class="label">{{ __('app.wishlist_source_url') }}</label>
					<div class="control">
						<input type="url" class="input" name="source_url" placeholder="https://...">
					</div>
				</div>

				<div class="field">
					<label class="label">{{ __('app.wishlist_price') }}</label>
					<div class="control">
						<input type="number" class="input" name="price" step="0.01" min="0">
					</div>
				</div>

				<div class="field">
					<label class="label">{{ __('app.wishlist_best_time_note') }}</label>
					<div class="control">
						<input type="text" class="input" name="best_time_note" placeholder="{{ __('app.wishlist_best_time_note_placeholder') }}">
					</div>
				</div>

				<div class="field">
					<label class="label">{{ __('app.wishlist_notes') }}</label>
					<div class="control">
						<textarea class="textarea" name="notes"></textarea>
					</div>
				</div>

				<div class="field">
					<label class="label">{{ __('app.photo') }}</label>
					<div class="control">
						<input type="file" name="photo" accept="image/*">
					</div>
				</div>
			</form>
		</section>
		<footer class="modal-card-foot is-stretched">
			<button class="button is-success" onclick="document.getElementById('frmAddWishlistItem').submit();">{{ __('app.add') }}</button>
			<button class="button" onclick="window.hideWishlistModals();">{{ __('app.cancel') }}</button>
		</footer>
	</div>
</div>

<div class="modal" id="wishlistEditModal">
	<div class="modal-background" onclick="window.hideWishlistModals();"></div>
	<div class="modal-card">
		<header class="modal-card-head is-stretched">
			<p class="modal-card-title">{{ __('app.edit_wishlist_item') }}</p>
			<button class="delete" aria-label="close" onclick="window.hideWishlistModals();"></button>
		</header>
		<section class="modal-card-body is-stretched">
			<form id="frmEditWishlistItem" method="POST" action="{{ url('/wishlist/edit') }}" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="item" id="inpEditWishlistItemId">

				<div class="field">
					<label class="label">{{ __('app.name') }}</label>
					<div class="control">
						<input type="text" class="input" name="name" id="inpEditWishlistName" required>
					</div>
				</div>

				<div class="field">
					<label class="label">{{ __('app.wishlist_species') }}</label>
					<div class="control">
						<input type="text" class="input" name="species" id="inpEditWishlistSpecies">
					</div>
				</div>

				<div class="field">
					<label class="label">{{ __('app.wishlist_cultivar') }}</label>
					<div class="control">
						<input type="text" class="input" name="cultivar" id="inpEditWishlistCultivar">
					</div>
				</div>

				<div class="field">
					<label class="label">{{ __('app.wishlist_priority') }}</label>
					<div class="control">
						<select class="input" name="priority" id="inpEditWishlistPriority">
							@foreach ($priorities as $priority_option)
								<option value="{{ $priority_option }}">{{ __('app.wishlist_priority_' . $priority_option) }}</option>
							@endforeach
						</select>
					</div>
				</div>

				<div class="field">
					<label class="label">{{ __('app.location') }}</label>
					<div class="control">
						<select class="input" name="location" id="inpEditWishlistLocation">
							<option value="">{{ __('app.wishlist_no_location') }}</option>
							@foreach ($locations as $loc)
								<option value="{{ $loc->get('id') }}">{{ $loc->get('name') }}</option>
							@endforeach
						</select>
					</div>
				</div>

				<div class="field">
					<label class="label">{{ __('app.wishlist_source_url') }}</label>
					<div class="control">
						<input type="url" class="input" name="source_url" id="inpEditWishlistSourceUrl" placeholder="https://...">
					</div>
				</div>

				<div class="field">
					<label class="label">{{ __('app.wishlist_price') }}</label>
					<div class="control">
						<input type="number" class="input" name="price" id="inpEditWishlistPrice" step="0.01" min="0">
					</div>
				</div>

				<div class="field">
					<label class="label">{{ __('app.wishlist_best_time_note') }}</label>
					<div class="control">
						<input type="text" class="input" name="best_time_note" id="inpEditWishlistBestTimeNote" placeholder="{{ __('app.wishlist_best_time_note_placeholder') }}">
					</div>
				</div>

				<div class="field">
					<label class="label">{{ __('app.wishlist_notes') }}</label>
					<div class="control">
						<textarea class="textarea" name="notes" id="inpEditWishlistNotes"></textarea>
					</div>
				</div>

				<div class="field">
					<label class="label">{{ __('app.photo') }}</label>
					<div class="control">
						<input type="file" name="photo" accept="image/*">
					</div>
					<p class="help">{{ __('app.wishlist_photo_replace_hint') }}</p>
				</div>
			</form>
		</section>
		<footer class="modal-card-foot is-stretched">
			<button class="button is-success" onclick="document.getElementById('frmEditWishlistItem').submit();">{{ __('app.save') }}</button>
			<button class="button" onclick="window.hideWishlistModals();">{{ __('app.cancel') }}</button>
		</footer>
	</div>
</div>

<script>
	window.showAddWishlistItem = function() {
		document.getElementById('frmAddWishlistItem').reset();
		document.getElementById('wishlistAddModal').classList.add('is-active');
	};

	window.showEditWishlistItem = function(id, data) {
		document.getElementById('inpEditWishlistItemId').value = id;
		document.getElementById('inpEditWishlistName').value = data.name || '';
		document.getElementById('inpEditWishlistSpecies').value = data.species || '';
		document.getElementById('inpEditWishlistCultivar').value = data.cultivar || '';
		document.getElementById('inpEditWishlistNotes').value = data.notes || '';
		document.getElementById('inpEditWishlistPriority').value = data.priority || 'would_like';
		document.getElementById('inpEditWishlistLocation').value = data.location || '';
		document.getElementById('inpEditWishlistSourceUrl').value = data.source_url || '';
		document.getElementById('inpEditWishlistPrice').value = data.price || '';
		document.getElementById('inpEditWishlistBestTimeNote').value = data.best_time_note || '';

		document.getElementById('wishlistEditModal').classList.add('is-active');
	};

	window.copyWishlistShareLink = function() {
		let el = document.getElementById('wishlistShareUrl');
		if (!el) {
			return;
		}

		el.select();

		try {
			if ((navigator.clipboard) && (navigator.clipboard.writeText)) {
				navigator.clipboard.writeText(el.value);
			} else {
				document.execCommand('copy');
			}
		} catch (e) {
			// Clipboard access can be denied by the browser - the link is
			// still selected, so the user can copy it manually either way.
		}
	};

	window.hideWishlistModals = function() {
		document.getElementById('wishlistAddModal').classList.remove('is-active');
		document.getElementById('wishlistEditModal').classList.remove('is-active');
	};

	// Prefills and opens the site-wide Add Plant modal (see layout.php) from
	// this item's own data attributes, then hands off entirely to that
	// existing flow - the wishlist item itself is only removed once a plant
	// is actually created from it (see PlantsController::add_plant).
	window.moveWishlistItemToCollection = function(id) {
		let el = document.getElementById('wishlist-title-' + id);
		if (!el) {
			return;
		}

		window.addNewPlant({
			name: el.dataset.name || '',
			species: el.dataset.species || '',
			notes: el.dataset.notes || '',
			location: el.dataset.location || null,
			wishlistItem: id
		});
	};
</script>
