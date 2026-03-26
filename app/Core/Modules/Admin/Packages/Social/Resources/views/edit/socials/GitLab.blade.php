<x-alert type="info" withClose="false" class="mb-0">
    {!! __('admin-social.edit.gitlab') !!}
</x-alert>

<x-forms.field>
    <x-forms.label for="settings__id" required>
        {{ __('admin-social.labels.application_id') }}
    </x-forms.label>
    <x-fields.input name="settings__id" id="settings__id"
        value="{{ request()->input('settings__id', $social ? $social->getSettings()['id'] : '') }}" required
        placeholder="{{ __('admin-social.edit.gitlab_id_placeholder') }}" />
</x-forms.field>

<x-forms.field>
    <x-forms.label for="settings__secret" required>
        {{ __('admin-social.labels.client_secret') }}
    </x-forms.label>
    <x-fields.input name="settings__secret" id="settings__secret" type="password"
        value="{{ request()->input('settings__secret', $social ? $social->getSettings()['secret'] : '') }}"
        placeholder="{{ __('admin-social.edit.gitlab_secret_placeholder') }}" required />
</x-forms.field>

<x-forms.field>
    <x-forms.label for="settings__scope">
        {{ __('admin-social.labels.scope') }}
    </x-forms.label>
    <x-fields.input name="settings__scope" id="settings__scope"
        value="{{ request()->input('settings__scope', $social ? ($social->getSettings()['scope'] ?? 'read_user') : 'read_user') }}"
        placeholder="read_user openid profile email" />
    <small class="text-muted">{{ __('admin-social.labels.scope_help') }}</small>
</x-forms.field>
