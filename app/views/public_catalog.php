<h1 class="title">{{ app('workspace') }}</h1>
<h2 class="subtitle">{{ __('app.public_catalog_hint') }}</h2>

@if ((is_countable($plants)) && (count($plants) > 0))
	<div class="public-catalog-grid">
		@foreach ($plants as $public_plant)
			<a class="public-catalog-item" href="{{ url('/public/plant/' . $public_plant->get('id')) }}">
				<div class="public-catalog-item-photo" style="background-image: url('{{ abs_photo($public_plant->get('photo')) }}');"></div>
				<div class="public-catalog-item-title">{{ $public_plant->get('name') }}</div>
			</a>
		@endforeach
	</div>
@else
	<p>{{ __('app.public_catalog_empty') }}</p>
@endif
