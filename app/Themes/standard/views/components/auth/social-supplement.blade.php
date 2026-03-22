<form class="auth-form mt-4">
    <x-forms.field class="mb-3">
        <x-forms.label for="login" required>@t('auth.user_login'):</x-forms.label>
        <x-fields.input type="text" name="login" id="login" value="{{ $login }}" required autofocus
            placeholder="{{ __('auth.user_login_placeholder') }}" />
    </x-forms.field>

    <x-forms.field class="mb-3">
        <x-forms.label for="email">@t('auth.email'):</x-forms.label>
        @if ($emailFromSocial && $email)
            <x-fields.input type="email" name="email" id="email" value="{{ $email }}" readonly />
        @else
            <x-fields.input type="email" name="email" id="email" value="{{ $email }}"
                placeholder="{{ __('auth.email_placeholder') }}" />
            <small class="form-text text-muted">@t('auth.supplement.email_hint')</small>
        @endif
    </x-forms.field>

    <x-forms.field class="mb-3">
        <x-forms.label for="password">@t('auth.password'):</x-forms.label>
        <x-fields.input type="password" name="password" id="password" value="{{ $password }}" toggle="true"
            placeholder="{{ __('auth.password_placeholder') }}" />
        <small class="form-text text-muted">@t('auth.supplement.password_hint')</small>
    </x-forms.field>

    <x-forms.field class="mb-4">
        <x-forms.label for="password_confirmation">@t('auth.password_confirmation'):</x-forms.label>
        <x-fields.input type="password" name="password_confirmation" id="password_confirmation"
            value="{{ $password_confirmation }}" toggle="true"
            placeholder="{{ __('auth.password_confirmation_placeholder') }}" />
    </x-forms.field>

    <div>
        <x-button yoyo:post="complete" yoyo:on="click" type="accent" class="w-100" withLoading>
            @t('auth.supplement.complete_button')
            <x-icon path="ph.regular.arrow-right" />
        </x-button>
    </div>
</form>
