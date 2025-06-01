<x-alert type="info" withClose="false" class="mb-0">
    {!! __('admin-social.edit.discord') !!}
</x-alert>

<x-forms.field>
    <x-forms.label for="settings__id" required>
        Client ID:
    </x-forms.label>
    <x-fields.input name="settings__id" id="settings__id"
        value="{{ request()->input('settings__id', $social ? $social->getSettings()['id'] : '') }}" required
        placeholder="Вставьте сюда ID приложения" />
</x-forms.field>

<x-forms.field>
    <x-forms.label for="settings__secret" required>Client Secret:</x-forms.label>
    <x-fields.input name="settings__secret" id="settings__secret" type="password"
        value="{{ request()->input('settings__secret', $social ? $social->getSettings()['secret'] : '') }}"
        placeholder="Вставьте сюда секретный ключ" required />
</x-forms.field>

<x-forms.field>
    <x-forms.label for="settings__token">
        {{ __('admin-social.edit.discord_token') }}
    </x-forms.label>
    <x-fields.input name="settings__token" id="settings__token"
        value="{{ request()->input('settings__token', $social ? $social->getSettings()['token'] : '') }}"
        placeholder="Вставьте сюда токен бота" type="password" />
    <small class="text-muted">
        {!! __('admin-social.edit.discord_token_help') !!}
    </small>
</x-forms.field>
