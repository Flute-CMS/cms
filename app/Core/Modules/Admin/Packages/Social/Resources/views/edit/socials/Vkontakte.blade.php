<x-alert type="info" withClose="false" class="mb-0">
    {!! __('admin-social.edit.vkontakte') !!}
</x-alert>

<x-forms.field>
    <x-forms.label for="settings__id" required>
        {{ __('admin-social.labels.application_id') }}
    </x-forms.label>
    <x-fields.input name="settings__id" id="settings__id"
        value="{{ request()->input('settings__id', $social ? $social->getSettings()['id'] : '') }}" required
        placeholder="{{ __('admin-social.edit.vkontakte_id_placeholder') }}" />
</x-forms.field>

<x-forms.field>
    <x-forms.label for="settings__secret" required>
        {{ __('admin-social.labels.secure_key') }}
    </x-forms.label>
    <x-fields.input name="settings__secret" id="settings__secret" type="password"
        value="{{ request()->input('settings__secret', $social ? $social->getSettings()['secret'] : '') }}"
        placeholder="{{ __('admin-social.edit.vkontakte_secret_placeholder') }}" required />
</x-forms.field>

<x-forms.field>
    <x-forms.label for="settings__scope">
        {{ __('admin-social.labels.scope') }}
    </x-forms.label>
    <x-fields.input name="settings__scope" id="settings__scope"
        value="{{ request()->input('settings__scope', $social ? ($social->getSettings()['scope'] ?? 'email') : 'email') }}"
        placeholder="email,offline" />
    <small class="text-muted">{{ __('admin-social.labels.scope_help') }}</small>
</x-forms.field>

<x-forms.field>
    <x-forms.label for="settings__version">
        {{ __('admin-social.labels.api_version') }}
    </x-forms.label>
    <x-fields.input name="settings__version" id="settings__version"
        value="{{ request()->input('settings__version', $social ? ($social->getSettings()['version'] ?? '5.131') : '5.131') }}"
        placeholder="5.131" />
    <small class="text-muted">{{ __('admin-social.labels.api_version_help') }}</small>
</x-forms.field>

<x-forms.field>
    <x-forms.label for="settings__service_token">
        {{ __('admin-social.labels.service_token') }}
    </x-forms.label>
    <x-fields.input name="settings__service_token" id="settings__service_token" type="password"
        value="{{ request()->input('settings__service_token', $social ? ($social->getSettings()['service_token'] ?? '') : '') }}"
        placeholder="vk1.a......" />
    <small class="text-muted">{{ __('admin-social.labels.service_token_help') }}</small>
</x-forms.field>
