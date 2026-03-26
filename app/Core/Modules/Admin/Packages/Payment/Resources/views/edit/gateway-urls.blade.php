<x-alert type="warning" withClose="false" class="mb-3">
    {{ __('admin-payment.fields.gateway_urls_alert') }}
</x-alert>

<x-forms.field>
    <x-forms.label>{{ __('admin-payment.fields.handle_url.label') }}</x-forms.label>
    <div class="input-wrapper">
        <div class="input__field-container">
            <input type="text" value="{{ $handleUrl }}" class="input__field" readonly onclick="this.select()" />
            <div class="input__postprefix">
                <button type="button" class="btn btn-sm" data-copy="{{ $handleUrl }}" data-tooltip="{{ __('def.copy') }}">
                    <x-icon path="ph.bold.copy-bold" />
                </button>
            </div>
        </div>
    </div>
    <small class="text-muted">{{ __('admin-payment.fields.handle_url.help') }}</small>
</x-forms.field>

<x-forms.field>
    <x-forms.label>{{ __('admin-payment.fields.success_url.label') }}</x-forms.label>
    <div class="input-wrapper">
        <div class="input__field-container">
            <input type="text" value="{{ $successUrl }}" class="input__field" readonly onclick="this.select()" />
            <div class="input__postprefix">
                <button type="button" class="btn btn-sm" data-copy="{{ $successUrl }}" data-tooltip="{{ __('def.copy') }}">
                    <x-icon path="ph.bold.copy-bold" />
                </button>
            </div>
        </div>
    </div>
    <small class="text-muted">{{ __('admin-payment.fields.success_url.help') }}</small>
</x-forms.field>

<x-forms.field>
    <x-forms.label>{{ __('admin-payment.fields.fail_url.label') }}</x-forms.label>
    <div class="input-wrapper">
        <div class="input__field-container">
            <input type="text" value="{{ $failUrl }}" class="input__field" readonly onclick="this.select()" />
            <div class="input__postprefix">
                <button type="button" class="btn btn-sm" data-copy="{{ $failUrl }}" data-tooltip="{{ __('def.copy') }}">
                    <x-icon path="ph.bold.copy-bold" />
                </button>
            </div>
        </div>
    </div>
    <small class="text-muted">{{ __('admin-payment.fields.fail_url.help') }}</small>
</x-forms.field>

<x-forms.field>
    <x-forms.label>{{ __('admin-payment.fields.method.label') }}</x-forms.label>
    <div class="input-wrapper">
        <div class="input__field-container">
            <input type="text" value="POST" class="input__field" readonly onclick="this.select()" />
            <div class="input__postprefix">
                <button type="button" class="btn btn-sm" data-copy="POST" data-tooltip="{{ __('def.copy') }}">
                    <x-icon path="ph.bold.copy-bold" />
                </button>
            </div>
        </div>
    </div>
    <small class="text-muted">{{ __('admin-payment.fields.method.help') }}</small>
</x-forms.field>
