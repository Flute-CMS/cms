<x-alert type="info" withClose="false" class="mb-0">
    {!! __('admin-social.edit.yahoo') !!}
</x-alert>

<x-forms.field>
    <x-forms.label for="settings__id" required>
        Client ID:
    </x-forms.label>
    <x-fields.input name="settings__id" id="settings__id"
        value="{{ request()->input('settings__id', $social ? $social->getSettings()['id'] : '') }}" required
        placeholder="{{ __('admin-social.edit.yahoo_id_placeholder') }}" />
</x-forms.field>

<x-forms.field>
    <x-forms.label for="settings__secret" required>Client Secret:</x-forms.label>
    <x-fields.input name="settings__secret" id="settings__secret" type="password"
        value="{{ request()->input('settings__secret', $social ? $social->getSettings()['secret'] : '') }}"
        placeholder="{{ __('admin-social.edit.yahoo_secret_placeholder') }}" required />
</x-forms.field>
