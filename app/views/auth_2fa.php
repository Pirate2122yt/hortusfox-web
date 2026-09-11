<!doctype html>
<html lang="{{ getLocale() }}" data-theme="{{ AppearanceModule::resolve() }}">
    <head>
        @include('head.php')

		<title>{{ app('workspace') }}</title>
    </head>

    <body>
        <div id="app" class="auth-main" style="background-image: url('{{ asset('img/background.jpg') }}');">
            <div class="auth-overlay">
                <div class="auth-content">
                    <div class="auth-header">
                        <img src="{{ asset('logo.png') }}" alt="Logo"/>

                        <h1>{{ app('workspace') }}</h1>
                    </div>

                    @if (FlashMessage::hasMsg('error'))
                    <div class="auth-info auth-info-error">
                        {{ FlashMessage::getMsg('error') }}
                    </div>
                    @elseif (FlashMessage::hasMsg('success'))
                    <div class="auth-info auth-info-success">
                        {{ FlashMessage::getMsg('success') }}
                    </div>
                    @else
                    <div class="auth-info">
                        {{ __('app.totp_login_hint') }}
                    </div>
                    @endif

                    <div class="auth-form">
                        <form method="POST" action="{{ url('/login/2fa') }}">
                            @csrf

                            <div class="field">
                                <div class="control">
                                    <input type="text" class="input totp-code-input" name="code" inputmode="numeric" autocomplete="one-time-code" placeholder="{{ __('app.totp_code_placeholder') }}" autofocus required/>
                                </div>
                            </div>

                            <div class="field">
                                <div class="control">
                                    <input type="submit" class="button is-info" value="{{ __('app.verify') }}"/>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="auth-help">
                        <a href="{{ url('/auth') }}">{{ __('app.cancel') }}</a>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
            });
        </script>
    </body>
</html>
