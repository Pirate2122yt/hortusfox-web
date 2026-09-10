@if (!$owner)
	<div class="public-catalog-empty">
		<i class="fas fa-gift"></i>
		<p>{{ __('app.public_wishlist_not_found') }}</p>
	</div>
@else
	<div class="public-catalog-header">
		<h1 class="title">{{ __('app.public_wishlist_title', ['name' => $owner->get('name')]) }}</h1>
		<h2 class="subtitle">{{ __('app.public_wishlist_hint') }}</h2>
	</div>

	@if ($total_price > 0)
		<p><strong>{{ __('app.wishlist_total_price') }}:</strong>&nbsp;{{ number_format($total_price, 2) }}</p>
	@endif

	@if ((is_countable($items)) && (count($items) > 0))
		<div class="public-catalog-grid">
			@foreach ($items as $wishlist_item)
				<div class="public-catalog-item">
					<div class="public-catalog-item-photo" style="background-image: url('{{ abs_photo($wishlist_item->get('photo')) }}');"></div>
					<div class="public-catalog-item-body">
						<div class="public-catalog-item-title">{{ $wishlist_item->get('name') }}</div>

						@if (($wishlist_item->get('species')) || ($wishlist_item->get('cultivar')))
							<div class="public-catalog-item-subtitle"><em>{{ trim($wishlist_item->get('species') . ' ' . (($wishlist_item->get('cultivar')) ? '(' . $wishlist_item->get('cultivar') . ')' : '')) }}</em></div>
						@endif

						<div style="margin-top: 8px;">
							<span class="tag {{ WishlistModel::getPriorityTagClass($wishlist_item->get('priority')) }}">{{ __('app.wishlist_priority_' . $wishlist_item->get('priority')) }}</span>
						</div>

						@if ($wishlist_item->get('location'))
							<div class="public-catalog-item-subtitle"><i class="fas fa-map-marker-alt"></i>&nbsp;{{ $locations_by_id[$wishlist_item->get('location')] ?? '?' }}</div>
						@endif

						@if ($wishlist_item->get('price') !== null)
							<div class="public-catalog-item-subtitle"><i class="fas fa-tag"></i>&nbsp;{{ number_format($wishlist_item->get('price'), 2) }}</div>
						@endif

						@if ($wishlist_item->get('best_time_note'))
							<div class="public-catalog-item-subtitle"><i class="fas fa-clock"></i>&nbsp;{{ $wishlist_item->get('best_time_note') }}</div>
						@endif

						@if ($wishlist_item->get('source_url'))
							<div class="public-catalog-item-subtitle"><a href="{{ $wishlist_item->get('source_url') }}" target="_blank" rel="noopener noreferrer"><i class="fas fa-link"></i>&nbsp;{{ __('app.wishlist_view_source') }}</a></div>
						@endif
					</div>
				</div>
			@endforeach
		</div>
	@else
		<div class="public-catalog-empty">
			<i class="fas fa-gift"></i>
			<p>{{ __('app.public_wishlist_empty') }}</p>
		</div>
	@endif
@endif
