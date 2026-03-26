@props([
    'label',
    'name',
    'id',
    'height',
    'spellcheck',
    'enableImageUpload',
    'imageUploadEndpoint',
    'value',
    'errors',
    'translatable',
])

@php
    $inputId = $id ?? ($name ?? 'editor-' . uniqid());
    $hasError = isset($errors) && $errors->has($name);
    $langs = \Flute\Admin\Platform\Fields\TranslatableInput::getLanguagesData();
    $isMultilang = isset($translatable) && $translatable && count($langs) >= 2;
    $plainValue = $isMultilang ? $value : transValue($value ?? '');
@endphp

@if ($isMultilang)
    <div class="translatable-wrap"
         data-translatable-languages='{!! json_encode($langs, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS) !!}'
         data-translatable-default="{{ config('lang.locale', 'en') }}">

        @isset($label)
            <label for="{{ $inputId }}" class="form-label mb-2">{{ $label }}</label>
        @endisset

        <div @class(['richtext-editor-wrapper', 'is-invalid' => $hasError])>
            <textarea {{ $attributes }} id="{{ $inputId }}"
                data-translatable-input
                data-translatable-name="{{ $name }}"
                data-editor="richtext" data-height="{{ $height ?? 300 }}"
                {!! isset($spellcheck) ? ' data-spellcheck="' . ($spellcheck ? 'true' : 'false') . '"' : '' !!}
                {!! isset($enableImageUpload) && $enableImageUpload ? ' data-upload="true"' : '' !!}
                {!! isset($imageUploadEndpoint) ? ' data-upload-url="' . $imageUploadEndpoint . '"' : '' !!}
            >{{ $value }}</textarea>

            @error($name)
                <span class="input__error">{{ $message }}</span>
            @enderror
        </div>
    </div>
@else
    @isset($label)
        <label for="{{ $inputId }}" class="form-label mb-2">{{ $label }}</label>
    @endisset

    <div @class(['richtext-editor-wrapper', 'is-invalid' => $hasError])>
        <textarea name="{{ $name }}" {{ $attributes }} id="{{ $inputId }}"
            data-editor="richtext" data-height="{{ $height ?? 300 }}"
            {!! isset($spellcheck) ? ' data-spellcheck="' . ($spellcheck ? 'true' : 'false') . '"' : '' !!}
            {!! isset($enableImageUpload) && $enableImageUpload ? ' data-upload="true"' : '' !!}
            {!! isset($imageUploadEndpoint) ? ' data-upload-url="' . $imageUploadEndpoint . '"' : '' !!}
        >{{ $plainValue }}</textarea>

        @error($name)
            <span class="input__error">{{ $message }}</span>
        @enderror
    </div>
@endif

@isset($help)
    <div class="form-text text-muted">{{ $help }}</div>
@endisset
