@php
    $required = $attributes->required ?? false;
    $disabled = $attributes->disabled ?? false;
    $multiple = $attributes->get('multiple', false);
    $mode = $mode ?? $attributes->get('mode', 'static');
    $plugins = $attributes->get('data-plugins', '[]');
    $placeholder = $attributes->get('placeholder', '');
    $options = $options ?? [];
    $isAllowEmpty = !empty($allowEmpty ?? false);

    $value = request()->input($name, $value);

    if ($multiple && !is_array($value) && !empty($value)) {
        $value = [$value];
    }
@endphp

<div class="field field--select" data-field>
    <div class="field__wrapper">
        <div class="select-wrapper" @if ($yoyo) yoyo hx-trigger="change delay:10ms" @endif>
            <select id="{{ $id }}" name="{{ $name }}" class="select__field"
                @if ($multiple) multiple @endif @if ($required) required @endif
                @if ($disabled) disabled @endif data-select data-mode="{{ $mode }}"
                data-max-items="{{ $maxItems }}" data-plugins="{{ $plugins }}"
                data-hide-selected="{{ $attributes->get('data-hide-selected', 'false') }}"
                placeholder="{{ $placeholder }}"
                data-allow-add="{{ $attributes->get('data-allow-add') }}"
                @if ($isAllowEmpty) data-allow-empty="true" @endif
                data-searchable="{{ $attributes->get('data-searchable', 'auto') }}"
                data-search-threshold="{{ $attributes->get('data-search-threshold', '6') }}"
                data-positioning="{{ $attributes->get('data-positioning', 'dropdown') }}"
                data-initial-value="{{ json_encode($value) }}"
                @if ($mode === 'async') data-search-url="{{ $attributes->get('data-search-url') }}"
                        data-search-min-length="{{ $attributes->get('data-search-min-length') }}"
                        data-search-delay="{{ $attributes->get('data-search-delay') }}"
                        data-search-fields="{{ $attributes->get('data-search-fields') }}"
                        data-entity="{{ $attributes->get('data-entity') }}"
                        data-display-field="{{ $attributes->get('data-display-field') }}"
                        data-value-field="{{ $attributes->get('data-value-field') }}"
                        data-preload="{{ $attributes->get('data-preload') }}"
                        @if ($attributes->get('data-extra-fields')) data-extra-fields="{{ $attributes->get('data-extra-fields') }}" @endif
                        @if ($attributes->get('data-option-view')) data-option-view="{{ $attributes->get('data-option-view') }}" @endif
                        @if ($attributes->get('data-item-view')) data-item-view="{{ $attributes->get('data-item-view') }}" @endif
                @endif
                @if ($attributes->get('renderOption')) data-render-option="{{ $attributes->get('renderOption') }}" @endif
                @if ($attributes->get('renderItem')) data-render-item="{{ $attributes->get('renderItem') }}" @endif
                @if ($attributes->get('renderNoResults')) data-render-no-results="{{ $attributes->get('renderNoResults') }}" @endif>

                @if ($mode === 'static' || $mode === 'database' || !empty($options))
                    @if (!$multiple && ($placeholder || $isAllowEmpty) && !array_key_exists('', $options))
                        <option value="">{{ $placeholder }}</option>
                    @endif
                    @foreach ($options as $optionValue => $optionLabel)
                        @if (is_array($optionLabel))
                            <option value="{{ $optionValue }}"
                                data-data="{{ json_encode($optionLabel) }}"
                                @if ($multiple && is_array($value))
                                    {{ in_array((string) $optionValue, array_map('strval', $value)) ? 'selected' : '' }}
                                @else
                                    {{ (string) $optionValue === (string) $value ? 'selected' : '' }}
                                @endif>
                                {{ $optionLabel['text'] ?? '' }}
                            </option>
                        @else
                            <option value="{{ $optionValue }}"
                                @if ($multiple && is_array($value))
                                    {{ in_array((string) $optionValue, array_map('strval', $value)) ? 'selected' : '' }}
                                @else
                                    {{ (string) $optionValue === (string) $value ? 'selected' : '' }}
                                @endif>
                                {{ $optionLabel }}
                            </option>
                        @endif
                    @endforeach
                @endif
            </select>
        </div>

        @error($name)
            <div class="input__error">{{ $message }}</div>
        @enderror
    </div>
</div>
