<?php $is_owner = (isset($user)) && (($wishlist_item->get('user') == $user->get('id')) || (UserModel::isCurrentlyAdmin())); ?>
<div class="plant-journal-entry" id="wishlist-entry-{{ $wishlist_item->get('id') }}">
	<div class="plant-journal-entry-body">
		@if ($wishlist_item->get('photo'))
			<div class="plant-journal-entry-photos">
				<img src="{{ abs_photo($wishlist_item->get('photo')) }}" alt="photo" style="max-width: 90px; border-radius: 6px;"/>
			</div>
		@endif

		<div class="plant-journal-entry-header">
			<span class="plant-journal-entry-title" id="wishlist-title-{{ $wishlist_item->get('id') }}"
				data-name="{{ $wishlist_item->get('name') }}"
				data-species="{{ $wishlist_item->get('species') }}"
				data-cultivar="{{ $wishlist_item->get('cultivar') }}"
				data-notes="{{ $wishlist_item->get('notes') }}"
				data-priority="{{ $wishlist_item->get('priority') }}"
				data-location="{{ $wishlist_item->get('location') }}"
				data-source_url="{{ $wishlist_item->get('source_url') }}"
				data-price="{{ $wishlist_item->get('price') }}"
				data-best_time_note="{{ $wishlist_item->get('best_time_note') }}">{{ $wishlist_item->get('name') }}</span>
			<span class="tag {{ WishlistModel::getPriorityTagClass($wishlist_item->get('priority')) }}">{{ __('app.wishlist_priority_' . $wishlist_item->get('priority')) }}</span>
		</div>

		@if (($wishlist_item->get('species')) || ($wishlist_item->get('cultivar')))
			<div><em>{{ trim($wishlist_item->get('species') . ' ' . (($wishlist_item->get('cultivar')) ? '(' . $wishlist_item->get('cultivar') . ')' : '')) }}</em></div>
		@endif

		@if (strlen(trim($wishlist_item->get('notes') ?? '')) > 0)
			<div class="plant-journal-entry-content">{{ $wishlist_item->get('notes') }}</div>
		@endif

		<div class="plant-journal-entry-content">
			@if ($wishlist_item->get('location'))
				<div><i class="fas fa-map-marker-alt is-color-darker"></i>&nbsp;{{ $locations_by_id[$wishlist_item->get('location')] ?? '?' }}</div>
			@endif

			@if ($wishlist_item->get('price') !== null)
				<div><i class="fas fa-tag is-color-darker"></i>&nbsp;{{ number_format($wishlist_item->get('price'), 2) }}</div>
			@endif

			@if ($wishlist_item->get('source_url'))
				<div><i class="fas fa-link is-color-darker"></i>&nbsp;<a href="{{ $wishlist_item->get('source_url') }}" target="_blank" rel="noopener noreferrer">{{ $wishlist_item->get('source_url') }}</a></div>
			@endif

			@if ($wishlist_item->get('best_time_note'))
				<div><i class="fas fa-clock is-color-darker"></i>&nbsp;{{ $wishlist_item->get('best_time_note') }}</div>
			@endif
		</div>

		<div class="plant-journal-entry-footer">
			<span class="plant-journal-entry-date">
				@if ((isset($view)) && ($view !== 'mine'))
					{{ __('app.wishlist_wanted_by') }} {{ $owners[$wishlist_item->get('user')] ?? '?' }} &middot;
				@endif
				{{ date('Y-m-d', strtotime($wishlist_item->get('created_at'))) }}
			</span>

			@if ($is_owner)
				<span class="plant-journal-entry-actions">
					<a href="javascript:void(0);" title="{{ __('app.move_to_collection') }}" onclick="window.moveWishlistItemToCollection('{{ $wishlist_item->get('id') }}');"><i class="fas fa-seedling is-color-darker"></i></a>&nbsp;
					<a href="javascript:void(0);" title="{{ __('app.edit') }}" onclick="let el = document.getElementById('wishlist-title-{{ $wishlist_item->get('id') }}'); window.showEditWishlistItem('{{ $wishlist_item->get('id') }}', el.dataset);"><i class="fas fa-edit is-color-darker"></i></a>&nbsp;<a href="javascript:void(0);" title="{{ __('app.remove') }}" onclick="if (confirm('{{ __('app.confirm_remove_wishlist_item') }}')) { document.getElementById('frmRemoveWishlistItem-{{ $wishlist_item->get('id') }}').submit(); }"><i class="fas fa-trash-alt is-color-darker"></i></a>

					<form id="frmRemoveWishlistItem-{{ $wishlist_item->get('id') }}" method="POST" action="{{ url('/wishlist/remove') }}" class="is-hidden">
						@csrf
						<input type="hidden" name="item" value="{{ $wishlist_item->get('id') }}">
					</form>
				</span>
			@endif
		</div>
	</div>
</div>
