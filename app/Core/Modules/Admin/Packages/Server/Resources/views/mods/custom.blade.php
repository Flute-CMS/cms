@props(['settings', 'driverName'])

<div class="alert alert-warning mb-0">
    <div class="d-flex">
        <div>
            <h5 class="alert-heading mb-1">{{ __('admin-server.mods.custom_alert.title') }}</h5>
            <p class="mb-0">{{ __('admin-server.mods.custom_alert.description') }}</p>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-12">
        <x-admin::forms.field name="custom_settings__name"
            label="{{ __('admin-server.mods.custom_settings_name.title') }}" required>
            <x-admin::fields.input name="custom_settings__name" id="custom_settings__name"
                value="{{ request()->input('custom_settings__name', $driverName) }}"
                placeholder="{{ __('admin-server.mods.custom_settings_name.placeholder') }}" required />
        </x-admin::forms.field>
    </div>

    <div class="col-md-12">
        <x-admin::forms.field name="custom_settings__json"
            label="{{ __('admin-server.mods.custom_settings_json.title') }}" required>
            <x-admin::fields.textarea name="custom_settings__json" id="custom_settings__json"
                value="{{ request()->input('custom_settings__json', json_encode($settings)) }}"
                placeholder="{{ __('admin-server.mods.custom_settings_json.placeholder') }}" required />
        </x-admin::forms.field>
    </div>
</div>
