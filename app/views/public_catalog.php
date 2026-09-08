<div class="public-catalog-header">
	<h1 class="title">{{ app('workspace') }}</h1>
	<h2 class="subtitle">{{ __('app.public_catalog_hint') }}</h2>
</div>

@if ((is_countable($plants)) && (count($plants) > 0))
	<div class="public-catalog-grid">
		@foreach ($plants as $public_plant)
			<a class="public-catalog-item" href="{{ url('/public/plant/' . $public_plant->get('id')) }}">
				<div class="public-catalog-item-photo" style="background-image: url('{{ abs_photo($public_plant->get('photo')) }}');"></div>
				<div class="public-catalog-item-body">
					<div class="public-catalog-item-title">{{ $public_plant->get('name') }}</div>
					@if ($public_plant->get('scientific_name'))
						<div class="public-catalog-item-subtitle"><em>{{ $public_plant->get('scientific_name') }}</em></div>
					@endif
				</div>
			</a>
		@endforeach
	</div>
@else
	<div class="public-catalog-empty">
		<i class="fas fa-seedling"></i>
		<p>{{ __('app.public_catalog_empty') }}</p>
	</div>
@endif
