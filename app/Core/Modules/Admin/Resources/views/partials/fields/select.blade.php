@php
    $required = $attributes->required ?? false;
    $disabled = $attributes->disabled ?? false;
    $multiple = $attributes->get('multiple', false);
    $mode = $attributes->get('mode', 'static');
    $plugins = $attributes->get('data-plugins', '[]');
    $placeholder = $attributes->get('placeholder', '');

    $value = request()->input($name, $value);
    
    // Преобразовать значение в массив, если это multiple селект
    if ($multiple && !is_array($value) && !empty($value)) {
        $value = [$value];
    }
@endphp

<div class="field field--select" data-field>
    <div class="field__wrapper">
        <div class="select-wrapper" @if ($yoyo) yoyo hx-trigger="change delay:10ms" @endif>
            <select id="{{ $id }}" name="{{ $name }}{{ $multiple ? '[]' : '' }}" class="select__field"
                @if ($multiple) multiple @endif @if ($required) required @endif
                @if ($disabled) disabled @endif data-select data-mode="{{ $mode }}"
                data-max-items="{{ $maxItems }}" data-plugins="{!! $plugins !!}"
                placeholder="{{ $placeholder }}"
                @if ($mode === 'async') data-search-url="{{ $attributes->get('data-search-url') }}"
                        data-search-min-length="{{ $attributes->get('data-search-min-length') }}"
                        data-search-delay="{{ $attributes->get('data-search-delay') }}"
                        data-search-fields="{!! $attributes->get('data-search-fields') !!}"
                        data-entity="{{ $attributes->get('data-entity') }}"
                        data-display-field="{{ $attributes->get('data-display-field') }}"
                        data-value-field="{{ $attributes->get('data-value-field') }}"
                        data-preload="{{ $attributes->get('data-preload') }}" @endif
                @if ($attributes->get('renderOption')) data-render-option="{{ $attributes->get('renderOption') }}" @endif
                @if ($attributes->get('renderItem')) data-render-item="{{ $attributes->get('renderItem') }}" @endif
                @if ($attributes->get('renderNoResults')) data-render-no-results="{{ $attributes->get('renderNoResults') }}" @endif>

                @if ($mode === 'static')
                    @if (!$multiple && $placeholder)
                        <option value="">{{ $placeholder }}</option>
                    @endif
                    @foreach ($options as $optionValue => $optionLabel)
                        <option value="{{ $optionValue }}"
                            @if ($multiple && is_array($value)) 
                                {{ in_array((string) $optionValue, array_map('strval', $value)) ? 'selected' : '' }}
                            @else
                                {{ (string) $optionValue === (string) $value ? 'selected' : '' }} 
                            @endif>
                            {{ $optionLabel }}
                        </option>
                    @endforeach
                @endif
            </select>
        </div>

        @error($name)
            <div class="input__error">{{ $message }}</div>
        @enderror
    </div>
</div>
