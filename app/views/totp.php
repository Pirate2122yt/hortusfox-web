<h1>{{ __('app.two_factor_auth') }}</h1>

<h2 class="smaller-headline">{{ __('app.totp_page_hint') }}</h2>

<div class="margin-vertical">
	<a class="is-default-link" href="{{ url('/profile') }}"><i class="fas fa-arrow-left"></i>&nbsp;{{ __('app.back_to_profile') }}</a>
</div>

@include('flashmsg.php')

@if ((is_countable($recovery_codes)) && (count($recovery_codes) > 0))
	<div class="settings-section">
		<strong>{{ __('app.totp_recovery_codes_title') }}</strong>
		<p>{{ __('app.totp_recovery_codes_hint') }}</p>

		<ul class="totp-recovery-code-list">
			@foreach ($recovery_codes as $recovery_code)
				<li><code>{{ $recovery_code }}</code></li>
			@endforeach
		</ul>

		<a class="button is-info" href="{{ url('/profile/2fa') }}">{{ __('app.totp_recovery_codes_done') }}</a>
	</div>
@elseif ($user->get('totp_enabled'))
	<div class="settings-section">
		<span class="tag is-success">{{ __('app.two_factor_enabled') }}</span>

		<p class="margin-vertical">{{ __('app.totp_disable_hint') }}</p>

		<form id="frmDisableTotp" method="POST" action="{{ url('/profile/2fa/disable') }}">
			@csrf

			<div class="field">
				<div class="control">
					<input type="password" class="input" name="password" placeholder="{{ __('app.enter_password') }}" required/>
				</div>
			</div>

			<div class="field">
				<div class="control">
					<button type="submit" class="button is-danger">{{ __('app.totp_disable') }}</button>
				</div>
			</div>
		</form>
	</div>
@elseif ($setup)
	<div class="settings-section">
		<span class="tag is-light">{{ __('app.two_factor_disabled') }}</span>

		<p class="margin-vertical">{{ __('app.totp_setup_hint') }}</p>

		<div class="totp-setup-qr">
			<img src="{{ $setup_qr }}" alt="QR code" style="max-width: 220px;"/>
		</div>

		<p>{{ __('app.totp_manual_entry_hint') }}</p>
		<p><code>{{ $setup['secret'] }}</code></p>

		<form id="frmConfirmTotp" method="POST" action="{{ url('/profile/2fa/confirm') }}">
			@csrf

			<div class="field">
				<div class="control">
					<input type="text" class="input totp-code-input" name="code" inputmode="numeric" autocomplete="one-time-code" placeholder="{{ __('app.totp_code_placeholder') }}" required/>
				</div>
			</div>

			<div class="field">
				<div class="control">
					<button type="submit" class="button is-success">{{ __('app.totp_confirm_setup') }}</button>
				</div>
			</div>
		</form>

		<form id="frmRestartTotp" method="POST" action="{{ url('/profile/2fa/begin') }}">
			@csrf
			<button type="submit" class="button is-small">{{ __('app.totp_restart_setup') }}</button>
		</form>
	</div>
@else
	<div class="settings-section">
		<span class="tag is-light">{{ __('app.two_factor_disabled') }}</span>

		<p class="margin-vertical">{{ __('app.totp_enable_hint') }}</p>

		<form id="frmBeginTotp" method="POST" action="{{ url('/profile/2fa/begin') }}">
			@csrf
			<button type="submit" class="button is-info">{{ __('app.totp_begin_setup') }}</button>
		</form>
	</div>
@endif
