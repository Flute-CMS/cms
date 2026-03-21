@php
    $hasError = $errors->has($name);
    $inputId = $id ?? $name ?? 'translatable-' . uniqid();
    $langs = \Flute\Admin\Platform\Fields\TranslatableInput::getLanguagesData();
    $isMultilang = count($langs) >= 2;
    $defaultLang = config('lang.locale', 'en');
    $fieldType = $type ?? 'text';
    $plainValue = transValue($value);

    // For multilang: extract default language content for initial display
    $initialDisplay = $plainValue;
    if ($isMultilang && $value && is_string($value)) {
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            $initialDisplay = $decoded[$defaultLang] ?? reset($decoded) ?: '';
        }
    }
@endphp

@if ($isMultilang)
    <div class="translatable-wrap"
         data-translatable-languages='{!! json_encode($langs, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS) !!}'
         data-translatable-default="{{ $defaultLang }}">
        @if ($fieldType === 'textarea')
            <div class="textarea__field-container @if($hasError) has-error @endif">
                <textarea
                    id="{{ $inputId }}"
                    data-translatable-input
                    data-translatable-name="{{ $name }}"
                    data-translatable-value="{{ $value }}"
                    class="textarea__field"
                    @if($placeholder) placeholder="{{ $placeholder }}" @endif
                    @if(isset($disabled) && $disabled) disabled @endif
                    @if(isset($readonly) && $readonly) readonly @endif
                >{{ $initialDisplay }}</textarea>
            </div>
        @else
            <div class="input__field-container @if($hasError) has-error @endif">
                <input type="text"
                    id="{{ $inputId }}"
                    data-translatable-input
                    data-translatable-name="{{ $name }}"
                    data-translatable-value="{{ $value }}"
                    value="{{ $initialDisplay }}"
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
        <div class="textarea__field-container @if($hasError) has-error @endif">
            <textarea
                id="{{ $inputId }}"
                name="{{ $name }}"
                class="textarea__field"
                @if($placeholder) placeholder="{{ $placeholder }}" @endif
                @if(isset($disabled) && $disabled) disabled @endif
                @if(isset($readonly) && $readonly) readonly @endif
            >{{ $plainValue }}</textarea>
        </div>
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
