@php
    $authUrl = url('/social/' . $driverKey);
    $bindUrl = url('/profile/social/bind/' . $driverKey);
@endphp

<x-alert type="warning" withClose="false" class="mb-3">
    {{ __('admin-social.fields.redirect_uri.alert') }}
</x-alert>

<x-forms.field>
    <x-forms.label>{{ __('admin-social.fields.redirect_uri.auth') }}</x-forms.label>
    <div class="input-wrapper">
        <div class="input__field-container">
            <input type="text" value="{{ $authUrl }}" class="input__field" readonly onclick="this.select()" />
            <div class="input__postprefix">
                <button type="button" class="btn btn-sm" data-copy="{{ $authUrl }}" data-tooltip="{{ __('def.copy') }}">
                    <x-icon path="ph.bold.copy-bold" />
                </button>
            </div>
        </div>
    </div>
    <small class="text-muted">{{ __('admin-social.fields.redirect_uri.auth_help') }}</small>
</x-forms.field>

<x-forms.field>
    <x-forms.label>{{ __('admin-social.fields.redirect_uri.bind') }}</x-forms.label>
    <div class="input-wrapper">
        <div class="input__field-container">
            <input type="text" value="{{ $bindUrl }}" class="input__field" readonly onclick="this.select()" />
            <div class="input__postprefix">
                <button type="button" class="btn btn-sm" data-copy="{{ $bindUrl }}" data-tooltip="{{ __('def.copy') }}">
                    <x-icon path="ph.bold.copy-bold" />
                </button>
            </div>
        </div>
    </div>
    <small class="text-muted">{{ __('admin-social.fields.redirect_uri.bind_help') }}</small>
</x-forms.field>
