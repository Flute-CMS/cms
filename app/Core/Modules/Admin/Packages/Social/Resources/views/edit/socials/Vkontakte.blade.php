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
    <x-forms.label for="settings__secret">
        {{ __('admin-social.labels.secure_key') }}
    </x-forms.label>
    <x-fields.input name="settings__secret" id="settings__secret" type="password"
        value="{{ request()->input('settings__secret', $social ? $social->getSettings()['secret'] : '') }}"
        placeholder="{{ __('admin-social.edit.vkontakte_secret_placeholder') }}" />
    <small class="text-muted">{{ __('admin-social.edit.vkontakte_secret_help') }}</small>
</x-forms.field>

<x-forms.field>
    <x-forms.label for="settings__scope">
        {{ __('admin-social.labels.scope') }}
    </x-forms.label>
    <x-fields.input name="settings__scope" id="settings__scope"
        value="{{ request()->input('settings__scope', $social ? ($social->getSettings()['scope'] ?? 'vkid.personal_info email') : 'vkid.personal_info email') }}"
        placeholder="vkid.personal_info email phone" />
    <small class="text-muted">{{ __('admin-social.edit.vkontakte_scope_help') }}</small>
</x-forms.field>
