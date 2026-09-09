<h1>{{ $place->get('name') }}</h1>

<div class="margin-vertical">
	<div class="action-strip action-strip-left">
		<div class="is-inline-block is-action-button-margin"><a class="is-default-link is-fixed-button-link is-fixed-margin-left-mobile" href="{{ url('/') }}">{{ __('app.back_to_dashboard') }}</a></div>
	</div>
</div>

@include('flashmsg.php')

<div class="locations">
	@if ((is_countable($locations)) && (count($locations) > 0))
		@foreach ($locations as $location)
			<a href="{{ url('/plants/location/' . $location->get('id')) }}">
				<div class="location" style="--bg-image: url('{{ UtilsModule::iconAsset($location->get('icon')) }}');">
					<div class="location-title">
						{{ $location->get('name') }}
					</div>

					<div class="location-footer">
						<div class="is-inline-block">
							<?php $plant_count = PlantsModel::getPlantCount($location->get('id')); ?>
							<span class="location-footer-count-desktop"><i class="fas fa-seedling is-color-ok"></i>&nbsp;{{ __('app.plant_count', ['count' => $plant_count]) }} &nbsp;</span>
							<span class="location-footer-count-mobile"><i class="fas fa-seedling is-color-ok"></i>&nbsp;{{ $plant_count }} &nbsp;</span>
						</div>

						<div class="is-inline-block">
							<?php $danger_count = PlantsModel::getDangerCount($location->get('id')); ?>

							<span class="location-footer-count-desktop">
								@if ($danger_count > 0)
									<i class="fas fa-exclamation-triangle is-color-danger"></i>&nbsp;{{ __('app.danger_count', ['count' => $danger_count]) }}
								@else
									<i class="far fa-check-circle is-color-ok"></i>&nbsp;{{ __('app.all_in_good_standing') }}
								@endif
							</span>

							<span class="location-footer-count-mobile">
								@if ($danger_count > 0)
									<i class="fas fa-exclamation-triangle is-color-danger"></i>&nbsp;{{ $danger_count }}
								@else
									<i class="far fa-check-circle is-color-ok"></i>&nbsp;{{ __('app.all_in_good_standing') }}
								@endif
							</span>
						</div>
					</div>
				</div>
			</a>
		@endforeach
	@else
		<div class="plants-empty">
			<div class="plants-empty-image">
				<img src="{{ asset('img/plantsempty.png') }}" alt="image"/>
			</div>

			<div class="plants-empty-text">{{ __('app.no_locations_in_place') }}</div>
		</div>
	@endif
</div>
