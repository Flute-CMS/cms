@php
    $hasError = $errors->has($name);
    $inputId = $id ?? $name ?? 'translatable-' . uniqid();
    $langs = \Flute\Admin\Platform\Fields\TranslatableInput::getLanguagesData();
    $isMultilang = count($langs) >= 2;
    $defaultLang = config('lang.locale', 'en');
    $fieldType = $type ?? 'text';
    // For single-lang mode, resolve JSON to plain string
    $plainValue = $isMultilang ? $value : transValue($value);
@endphp

@if ($isMultilang)
    <div class="translatable-wrap"
         data-translatable-languages='{!! json_encode($langs, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS) !!}'
         data-translatable-default="{{ $defaultLang }}">
        @if ($fieldType === 'textarea')
            <textarea
                id="{{ $inputId }}"
                data-translatable-input
                data-translatable-name="{{ $name }}"
                @class(['input__field', 'has-error' => $hasError])
                @if($placeholder) placeholder="{{ $placeholder }}" @endif
                @if(isset($disabled) && $disabled) disabled @endif
                @if(isset($readonly) && $readonly) readonly @endif
            >{{ $value }}</textarea>
        @else
            <div class="input__field-container @if($hasError) has-error @endif">
                <input type="text"
                    id="{{ $inputId }}"
                    data-translatable-input
                    data-translatable-name="{{ $name }}"
                    value="{{ $value }}"
                    @if($placeholder) placeholder="{{ $placeholder }}" @endif
                    @if(isset($disabled) && $disabled) disabled @endif
                    @if(isset($readonly) && $readonly) readonly @endif
                    class="input__field"
                />
            </div>
        @endif

        @error($name)
            <span class="input__error">{{ $message }}</span>
        @enderror
    </div>
@else
    {{-- Single language — plain input --}}
    @if ($fieldType === 'textarea')
        <textarea
            id="{{ $inputId }}"
            name="{{ $name }}"
            @class(['input__field', 'has-error' => $hasError])
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            @if(isset($disabled) && $disabled) disabled @endif
            @if(isset($readonly) && $readonly) readonly @endif
        >{{ $plainValue }}</textarea>
    @else
        <div class="input__field-container @if($hasError) has-error @endif">
            <input type="text"
                id="{{ $inputId }}"
                name="{{ $name }}"
                value="{{ $plainValue }}"
                @if($placeholder) placeholder="{{ $placeholder }}" @endif
                @if(isset($disabled) && $disabled) disabled @endif
                @if(isset($readonly) && $readonly) readonly @endif
                class="input__field"
            />
        </div>
    @endif

    @error($name)
        <span class="input__error">{{ $message }}</span>
    @enderror
@endif
