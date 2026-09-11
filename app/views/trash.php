<h1>{{ __('app.recycle_bin') }}</h1>

<h2 class="smaller-headline">{{ __('app.trash_hint') }}</h2>

<div class="margin-vertical">
	<a class="is-default-link" href="{{ url('/plants') }}"><i class="fas fa-arrow-left"></i>&nbsp;{{ __('app.back_to_all_plants') }}</a>
</div>

@include('flashmsg.php')

@if ((is_countable($trashed_plants)) && (count($trashed_plants) > 0))
	@if (UserModel::isCurrentlyAdmin())
		<div class="margin-vertical">
			<form id="frmEmptyTrash" method="POST" action="{{ url('/plants/trash/empty') }}" onsubmit="return confirm(window.EMPTY_TRASH_CONFIRM);">
				@csrf
				<button type="submit" class="button is-danger is-small">{{ __('app.empty_trash') }}</button>
			</form>
		</div>
	@endif

	<div class="plant-journal-entries" id="trash-entries">
		@foreach ($trashed_plants as $plant)
			<div class="plant-journal-entry" id="trash-entry-{{ $plant->get('id') }}">
				<div class="plant-journal-entry-body">
					<div class="plant-journal-entry-photos">
						<img src="{{ abs_photo($plant->get('photo')) }}" alt="photo" style="max-width: 90px; border-radius: 6px;"/>
					</div>

					<div class="plant-journal-entry-header">
						<span class="plant-journal-entry-title">{{ $plant->get('name') }}</span>
					</div>

					<div class="plant-journal-entry-content">
						<i class="fas fa-map-marker-alt is-color-darker"></i>&nbsp;{{ LocationsModel::getNameById($plant->get('location')) ?? '?' }}
					</div>

					<div class="plant-journal-entry-footer">
						<span class="plant-journal-entry-date">
							{{ __('app.deleted_on') }} {{ date('Y-m-d', strtotime($plant->get('deleted_at'))) }}
						</span>

						<span class="plant-journal-entry-actions">
							<form id="frmRestorePlant-{{ $plant->get('id') }}" method="POST" action="{{ url('/plants/trash/restore') }}" class="is-inline-block">
								@csrf
								<input type="hidden" name="plant" value="{{ $plant->get('id') }}">
								<button type="submit" class="button is-small is-success">{{ __('app.restore') }}</button>
							</form>

							@if (UserModel::isCurrentlyAdmin())
								&nbsp;<form id="frmPurgePlant-{{ $plant->get('id') }}" method="POST" action="{{ url('/plants/trash/purge') }}" class="is-inline-block" onsubmit="return confirm(window.PURGE_PLANT_CONFIRM);">
									@csrf
									<input type="hidden" name="plant" value="{{ $plant->get('id') }}">
									<button type="submit" class="button is-small is-danger">{{ __('app.delete_forever') }}</button>
								</form>
							@endif
						</span>
					</div>
				</div>
			</div>
		@endforeach
	</div>
@else
	<strong>{{ __('app.no_trashed_plants') }}</strong>
@endif

<script>
	// json_encode (not a plain double-curly interpolation) so an
	// apostrophe in the translated text can't break out of the
	// single-quoted JS string it's used in below. Same reasoning as
	// chat.php's CHAT_DELETE_CONFIRM.
	window.PURGE_PLANT_CONFIRM = {!! json_encode(__('app.confirm_purge_plant')) !!};
	window.EMPTY_TRASH_CONFIRM = {!! json_encode(__('app.confirm_empty_trash')) !!};
</script>
