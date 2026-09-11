<h1>{{ __('app.favorites') }}</h1>

<h2 class="smaller-headline">{{ __('app.favorites_hint') }}</h2>

<div class="margin-vertical">
	<a class="is-default-link" href="{{ url('/plants') }}"><i class="fas fa-arrow-left"></i>&nbsp;{{ __('app.back_to_all_plants') }}</a>
</div>

@include('flashmsg.php')

@if ((is_countable($favorite_plants)) && (count($favorite_plants) > 0))
	<div class="plant-journal-entries" id="favorite-entries">
		@foreach ($favorite_plants as $plant)
			<div class="plant-journal-entry" id="favorite-entry-{{ $plant->get('id') }}">
				<div class="plant-journal-entry-body">
					<div class="plant-journal-entry-photos">
						<img src="{{ abs_photo($plant->get('photo')) }}" alt="photo" style="max-width: 90px; border-radius: 6px;"/>
					</div>

					<div class="plant-journal-entry-header">
						<span class="plant-journal-entry-title"><a class="is-default-link" href="{{ url('/plants/details/' . $plant->get('id')) }}">{{ $plant->get('name') }}</a></span>
					</div>

					<div class="plant-journal-entry-content">
						<i class="fas fa-map-marker-alt is-color-darker"></i>&nbsp;{{ LocationsModel::getNameById($plant->get('location')) ?? '?' }}
					</div>

					<div class="plant-journal-entry-footer">
						<span></span>

						<span class="plant-journal-entry-actions">
							<form id="frmUnfavorite-{{ $plant->get('id') }}" method="POST" action="{{ url('/plants/favorites/remove') }}" class="is-inline-block">
								@csrf
								<input type="hidden" name="plant" value="{{ $plant->get('id') }}">
								<button type="submit" class="button is-small"><i class="fas fa-star"></i>&nbsp;{{ __('app.unfavorite') }}</button>
							</form>
						</span>
					</div>
				</div>
			</div>
		@endforeach
	</div>
@else
	<strong>{{ __('app.no_favorite_plants') }}</strong>
@endif
