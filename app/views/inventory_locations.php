<h1>{{ __('app.inventory') }}</h1>

<h2 class="smaller-headline">{{ __('app.inventory_select_location_hint') }}</h2>

@include('flashmsg.php')

@if ((count($places) === 0) && (count($unassigned_locations) === 0))
    <p>{{ __('app.inventory_no_locations') }}</p>
@endif

@foreach ($places as $place)
    @if (count(LocationsModel::getByPlace($place->get('id'))) > 0)
        <h3 class="inventory-place-header">{{ $place->get('name') }}</h3>

        <div class="locations">
            @foreach (LocationsModel::getByPlace($place->get('id')) as $location)
                <a href="{{ url('/inventory/location/' . $location->get('id')) }}">
                    <div class="location" style="--bg-image: url('{{ UtilsModule::iconAsset($location->get('icon')) }}');">
                        <div class="location-title">
                            {{ $location->get('name') }}
                        </div>

                        <div class="location-footer">
                            <div class="is-inline-block">
                                <?php $item_count = InventoryModel::getCountByLocation($location->get('id')); ?>
                                <i class="fas fa-box"></i>&nbsp;{{ __('app.inventory_item_count', ['count' => $item_count]) }}
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endforeach

@if (count($unassigned_locations) > 0)
    <h3 class="inventory-place-header">{{ __('app.locations') }}</h3>

    <div class="locations">
        @foreach ($unassigned_locations as $location)
            <a href="{{ url('/inventory/location/' . $location->get('id')) }}">
                <div class="location" style="--bg-image: url('{{ UtilsModule::iconAsset($location->get('icon')) }}');">
                    <div class="location-title">
                        {{ $location->get('name') }}
                    </div>

                    <div class="location-footer">
                        <div class="is-inline-block">
                            <?php $item_count = InventoryModel::getCountByLocation($location->get('id')); ?>
                            <i class="fas fa-box"></i>&nbsp;{{ __('app.inventory_item_count', ['count' => $item_count]) }}
                        </div>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
@endif

<h3 class="inventory-place-header">{{ __('app.inventory_unassigned') }}</h3>

<div class="locations">
    <a href="{{ url('/inventory/unassigned') }}">
        <div class="location">
            <div class="location-title">
                {{ __('app.inventory_unassigned') }}
            </div>

            <div class="location-footer">
                <div class="is-inline-block">
                    <i class="fas fa-box"></i>&nbsp;{{ __('app.inventory_item_count', ['count' => $unassigned_count]) }}
                </div>
            </div>
        </div>
    </a>
</div>
