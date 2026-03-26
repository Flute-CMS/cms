@php
    $hasError = $errors->has($name);
    $inputId = $id ?? $name ?? 'editor-' . uniqid();
    $langs = \Flute\Admin\Platform\Fields\TranslatableInput::getLanguagesData();
    $isMultilang = count($langs) >= 2;
    $defaultLang = config('lang.locale', 'en');
    $plainValue = $isMultilang ? $value : transValue($value);

    // For multilang richtext: parse JSON and give editor the default language content,
    // store full JSON in data-attribute for JS to read
    $editorInitialContent = $plainValue;
    if ($isMultilang && $value) {
        $decoded = is_string($value) ? json_decode($value, true) : null;
        $editorInitialContent = is_array($decoded) ? ($decoded[$defaultLang] ?? reset($decoded) ?: '') : $value;
    }
@endphp

@if ($isMultilang)
    <div class="translatable-wrap"
         data-translatable-languages='{!! json_encode($langs, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS) !!}'
         data-translatable-default="{{ $defaultLang }}">

        @isset($label)
            <label for="{{ $inputId }}" class="form-label mb-2">{{ $label }}</label>
        @endisset

        <div @class(['richtext-editor-wrapper', 'is-invalid' => $hasError])>
            <textarea
                id="{{ $inputId }}"
                data-translatable-input
                data-translatable-name="{{ $name }}"
                data-translatable-value="{{ $value }}"
                data-editor="richtext"
                data-height="{{ $height ?? 300 }}"
                {!! isset($spellcheck) ? ' data-spellcheck="' . ($spellcheck ? 'true' : 'false') . '"' : '' !!}
                {!! isset($enableImageUpload) && $enableImageUpload ? ' data-upload="true"' : '' !!}
                {!! isset($imageUploadEndpoint) ? ' data-upload-url="' . $imageUploadEndpoint . '"' : '' !!}
            >{{ $editorInitialContent }}</textarea>

            @error($name)
                <span class="input__error">{{ $message }}</span>
            @enderror
        </div>
    </div>
@else
    {{-- Single language — standard richtext --}}
    @isset($label)
        <label for="{{ $inputId }}" class="form-label mb-2">{{ $label }}</label>
    @endisset

    <div @class(['richtext-editor-wrapper', 'is-invalid' => $hasError])>
        <textarea
            id="{{ $inputId }}"
            name="{{ $name }}"
            data-editor="richtext"
            data-height="{{ $height ?? 300 }}"
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
