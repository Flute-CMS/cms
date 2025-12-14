@if ($showTwoFactor ?? false)
    <div class="two-factor-verify">
        <input type="hidden" name="pendingUserId" value="{{ $pendingUserId ?? '' }}" />
        <input type="hidden" name="showTwoFactor" value="1" />

        <x-icon path="ph.regular.shield-check" class="two-factor-verify__icon" />
        <h4 class="two-factor-verify__title">{{ __('auth.two_factor.enter_code') }}</h4>
        <p class="two-factor-verify__subtitle">{{ __('auth.two_factor.enter_code_description') }}</p>

        <x-forms.field class="two-factor-verify__input">
            <x-fields.otp-input name="twoFactorCode" id="twoFactorCode"
                value="{{ $twoFactorCode ?? '' }}" placeholder="{{ __('auth.two_factor.verify_code_placeholder') }}"
                maxlength="20" autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]*" />
        </x-forms.field>

        <div class="two-factor-verify__actions">
            <x-button yoyo:post="cancelTwoFactor" yoyo:on="click" type="outline-primary">
                {{ __('def.back') }}
            </x-button>
            <x-button yoyo:post="verifyTwoFactor" yoyo:on="click" type="accent" withLoading>
                {{ __('def.continue') }}
                <x-icon path="ph.regular.arrow-right" />
            </x-button>
        </div>
    </div>
@else
    <form class="auth-form @if (empty(social()->getAll())) mt-4 @endif">
        <x-forms.field class="mb-3">
            <x-forms.label for="loginOrEmail" required>@t('auth.login_or_email'):</x-forms.label>
            <x-fields.input autofocus name="loginOrEmail" id="loginOrEmail" value="{{ $loginOrEmail }}" required
                placeholder="{{ __('auth.login_or_email_placeholder') }}" />
        </x-forms.field>

        <x-forms.field class="mb-3">
            <div class="flex-between auth__password">
                <x-forms.label for="password" required>
                    @t('auth.password'):
                </x-forms.label>

                @if (config('auth.reset_password'))
                    <x-link href="{{ url('/forgot-password') }}">@t('auth.forgot_password')</x-link>
                @endif
            </div>
            <x-fields.input type="password" name="password" id="password" value="{{ $password }}" toggle="true"
                required placeholder="{{ __('auth.password_placeholder') }}" />
        </x-forms.field>

        <x-forms.field class="mb-4">
            <x-fields.checkbox name="rememberMe" id="rememberMe" checked="{{ $rememberMe }}"
                label="{{ __('auth.remember_me') }}" />
        </x-forms.field>

        @include('flute::components.captcha', ['action' => 'login'])

        <x-button yoyo:post="login" yoyo:on="click" type="accent" class="w-100" withLoading>
            @t('def.login')
            <x-icon path="ph.regular.arrow-right" />
        </x-button>
    </form>
@endif
