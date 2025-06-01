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

            @if(config('auth.reset_password'))
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

