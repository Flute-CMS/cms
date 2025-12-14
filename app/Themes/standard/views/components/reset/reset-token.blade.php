<form class="auth-form">
    <input type="hidden" name="token" value="{{ $token }}">

    <x-forms.field class="mb-4">
        <x-forms.label for="password" required>@t('auth.password'):</x-forms.label>
        <x-fields.input type="password" name="password" id="password" value="{{ $password }}" toggle="true" required
            placeholder="{{ __('auth.password_placeholder') }}" />
    </x-forms.field>

    <x-forms.field class="mb-4">
        <x-forms.label for="password_confirmation" required>@t('auth.password_confirmation'):</x-forms.label>
        <x-fields.input type="password" name="password_confirmation" id="password_confirmation"
            value="{{ $password_confirmation }}" toggle="true" required
            placeholder="{{ __('auth.password_confirmation_placeholder') }}" />
    </x-forms.field>

    <x-button yoyo:post="reset" yoyo:on="click" type="accent" class="w-100" withLoading>
        @t('auth.reset.reset_password')
    </x-button>
</form>
