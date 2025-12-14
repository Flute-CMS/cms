@props(['driverKey'])

<div class="alert alert-info mb-3">
    <div class="d-flex">
        <x-icon path="ph.bold.info-bold" class="me-3 fs-4" />
        <div>
            <h4 class="alert-heading mb-1">{{ __('admin-payment.edit.gateway_title', ['driver' => $driverKey]) }}</h4>
            <p class="mb-0">{{ __('admin-payment.edit.gateway_description') }}</p>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <x-admin::forms.field
            name="settings__id"
            label="Client ID"
            required>
            <x-admin::fields.input
                name="settings__id"
                placeholder="{{ __('admin-payment.edit.client_id_placeholder') }}"
                required />
        </x-admin::forms.field>
    </div>

    <div class="col-md-6">
        <x-admin::forms.field
            name="settings__secret"
            label="Client Secret"
            required>
            <x-admin::fields.input
                name="settings__secret"
                type="password"
                placeholder="{{ __('admin-payment.edit.client_secret_placeholder') }}"
                required />
        </x-admin::forms.field>
    </div>
</div> 