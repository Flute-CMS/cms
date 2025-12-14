@if (!$success)
    <form class="auth-form">
        <x-forms.field class="mb-4">
            <x-forms.label for="loginOrEmail" required>@t('auth.login_or_email'):</x-forms.label>
            <x-fields.input name="loginOrEmail" id="loginOrEmail" value="{{ !$success ? $loginOrEmail : '' }}" required
                autofocus placeholder="{{ __('auth.login_or_email_placeholder') }}" />
        </x-forms.field>

        @include('flute::components.captcha', ['action' => 'password_reset'])

        <x-button yoyo:post="reset" yoyo:on="click" type="accent" class="w-100" withLoading>
            @t('auth.reset.send_link')
        </x-button>
    </form>
@else
    <div class="reset-container">
        <x-icon path="ph.regular.check-circle" class="reset-success-icon" />
        <p>{{ __('auth.reset.success_reset') }}</p>
    </div>
@endif
