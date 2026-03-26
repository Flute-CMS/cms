<x-alert type="info" withClose="false" class="mb-0">
    {!! __('admin-social.edit.twitter') !!}
</x-alert>

<x-forms.field>
    <x-forms.label for="settings__id" required>
        {{ __('admin-social.labels.api_key') }}
    </x-forms.label>
    <x-fields.input name="settings__id" id="settings__id"
        value="{{ request()->input('settings__id', $social ? $social->getSettings()['id'] : '') }}" required
        placeholder="{{ __('admin-social.edit.twitter_id_placeholder') }}" />
</x-forms.field>

<x-forms.field>
    <x-forms.label for="settings__secret" required>
        {{ __('admin-social.labels.api_secret') }}
    </x-forms.label>
    <x-fields.input name="settings__secret" id="settings__secret" type="password"
        value="{{ request()->input('settings__secret', $social ? $social->getSettings()['secret'] : '') }}"
        placeholder="{{ __('admin-social.edit.twitter_secret_placeholder') }}" required />
</x-forms.field>
