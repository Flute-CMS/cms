@props([
    'type' => 'text',
    'name' => '',
    'value' => '',
    'prefix' => '',
    'readOnly' => false,
    'postPrefix' => false,
    'toggle' => true,
    'withoutBottom' => false,
    'filePond' => false,
    'filePondOptions' => [],
    'defaultFile' => null,
])

@php
    $hasError = $errors->has($name);
@endphp

<div class="input-wrapper">
    <div @class(['input__field-container', 'input__field-container-readonly' => $readOnly, 'has-error' => $hasError])>
        @if ($prefix)
            <span class="input__prefix">{{ $prefix }}</span>
        @endif

        @if ($type === 'file' && $filePond)
            <input type="file" name="{{ $name }}" id="{{ $attributes->get('id', $name) }}"
                class="filepond input__field" data-default-file="{{ $defaultFile }}"
                data-file-pond-options='@json($filePondOptions)' data-accept="{{ $attributes->get('accept', '') }}"
                {{ $hasError ? 'aria-invalid=true' : '' }}
                {{ $attributes->merge(['class' => 'filepond input__field']) }} />
        @else
            <input type="{{ $type }}" name="{{ $name }}" id="{{ $attributes->get('id', $name) }}"
                {{ $hasError ? 'aria-invalid=true' : '' }} value="{{ $value }}"
                {{ $attributes->class(['input__field-withPassword' => $type === 'password'])->merge(['class' => 'input__field']) }} />
        @endif

        @if ($type === 'password' && $toggle)
            <button type="button" onclick="togglePassword(event)" class="input__toggle-btn"
                aria-label="Toggle Password Visibility">
                <x-icon class="icon-eye" path="ph.regular.eye" />
                <x-icon class="icon-eye-slash" path="ph.regular.eye-slash" style="display: none;" />
            </button>
        @endif

        @if ($postPrefix)
            <div class="input__postprefix">{!! $postPrefix !!}</div>
        @endif
    </div>

    @error($name)
        <span class="input__error">{{ $message }}</span>
    @enderror
</div>
