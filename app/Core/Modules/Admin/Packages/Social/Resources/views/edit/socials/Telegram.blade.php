<x-alert type="info" withClose="false" class="mb-0">
    {!! __('admin-social.edit.telegram') !!}
</x-alert>

<x-forms.field>
    <x-forms.label for="settings__id" required>
        {{ __('admin-social.edit.telegram_client_id') }}
    </x-forms.label>
    <x-fields.input name="settings__id" id="settings__id" required
        value="{{ request()->input('settings__id', $social ? $social->getSettings()['id'] : '') }}"
        placeholder="{{ __('admin-social.edit.telegram_client_id_placeholder') }}" />
</x-forms.field>

<x-forms.field>
    <x-forms.label for="settings__secret" required>
        {{ __('admin-social.edit.telegram_client_secret') }}
    </x-forms.label>
    <x-fields.input name="settings__secret" id="settings__secret" required
        value="{{ request()->input('settings__secret', $social ? $social->getSettings()['secret'] : '') }}"
        placeholder="{{ __('admin-social.edit.telegram_client_secret_placeholder') }}" />
</x-forms.field>
