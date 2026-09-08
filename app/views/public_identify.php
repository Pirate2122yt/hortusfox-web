<a class="public-plant-back" href="{{ url('/public') }}">&larr; {{ __('app.public_back_to_catalog') }}</a>

@include('flashmsg.php')

<h1 class="title">{{ __('app.public_identify_title') }}</h1>
<h2 class="subtitle">{{ __('app.public_identify_hint') }}</h2>

@if (!$available)
	<div class="public-identify-unavailable">
		<i class="fas fa-camera-retro"></i>
		<p>{{ __('app.public_identify_unavailable') }}</p>
	</div>
@else
	<div class="public-identify-form-wrap">
		<form method="POST" action="{{ url('/public/identify') }}" enctype="multipart/form-data" id="public-identify-form">
			@csrf

			<div class="public-hp-field" aria-hidden="true">
				<label>Website</label>
				<input type="text" name="website" tabindex="-1" autocomplete="off"/>
			</div>

			<div class="field">
				<label class="label">{{ __('app.public_identify_photo_label') }}</label>
				<div class="control">
					<input type="file" class="input public-identify-file" name="photo" accept="image/png,image/jpeg,image/gif" required>
				</div>
			</div>

			@if (app('public_captcha_sitekey'))
				<div class="field">
					<div class="cf-turnstile" data-sitekey="{{ app('public_captcha_sitekey') }}"></div>
				</div>
			@endif

			<div class="control">
				<button type="submit" class="button is-success" id="public-identify-submit">{{ __('app.public_identify_submit') }}</button>
			</div>

			<p class="public-identify-remaining">{{ str_replace(['{count}', '{limit}'], [$remaining, PublicIdentifyRequestModel::DAILY_LIMIT], __('app.public_identify_remaining')) }}</p>
		</form>
	</div>

	@if (app('public_captcha_sitekey'))
		<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
	@endif

	<script>
		document.getElementById('public-identify-form').addEventListener('submit', function() {
			var btn = document.getElementById('public-identify-submit');
			btn.disabled = true;
			btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>&nbsp;' + btn.innerHTML;
		});
	</script>

	@if (is_array($results))
		<h3 class="title is-5 public-identify-results-title">{{ __('app.public_identify_results_title') }}</h3>

		@if (count($results) > 0)
			<div class="public-identify-results">
				@foreach (array_slice($results, 0, 5) as $public_identify_result)
					<div class="public-identify-result">
						<div class="public-identify-result-score">{{ number_format($public_identify_result->score * 100, 1) }}%</div>
						<div>
							<div class="public-identify-result-name"><em>{{ $public_identify_result->species->scientificNameWithoutAuthor ?? $public_identify_result->species->scientificName }}</em></div>
							@if ((isset($public_identify_result->species->commonNames)) && (is_array($public_identify_result->species->commonNames)) && (count($public_identify_result->species->commonNames) > 0))
								<div class="public-identify-result-common">{{ str_replace('{names}', implode(', ', $public_identify_result->species->commonNames), __('app.public_identify_common_names')) }}</div>
							@endif
						</div>
					</div>
				@endforeach
			</div>
		@else
			<p class="public-no-entries">{{ __('app.public_identify_no_match') }}</p>
		@endif
	@endif
@endif
