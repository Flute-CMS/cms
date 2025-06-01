<header class="installer__header">
    <h1>{{ __('install.flute_key.title') }}</h1>
    <p>{{ __('install.flute_key.description') }}</p>
</header>

<div class="flute-key-step">
    <div class="flute-key-step__form">
        <x-input name="fluteKey" :label="__('install.flute_key.label')" :value="request()->input('fluteKey', $fluteKey)"
            :placeholder="__('install.flute_key.placeholder')" hx-push-url="false" />

        @if(! $isValid && $fluteKey)
            <small class="flute-key-step__error-message">{{ __('install.flute_key.error_invalid') }}</small>
        @endif

        @if($isValid && $fluteKey)
            <small class="flute-key-step__success-message">{{ __('install.flute_key.success') }}</small>
        @endif
    </div>

    <div class="flute-key-step__actions">
        <x-button class="w-full" hx-get="{{ route('installer.step', ['id' => 2]) }}" hx-target="main" hx-push-url="true"
            hx-trigger="click" variant="secondary" yoyo:ignore>
            <x-icon path="ph.regular.arrow-left" />
            {{ __('install.common.back') }}
        </x-button>

        <x-button class="w-full" yoyo:post="validateKey" variant="primary">
            {{ __('install.common.next') }}
            <x-icon path="ph.regular.arrow-up-right" />
        </x-button>
    </div>
</div>