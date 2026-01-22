@props([
    'name',
    'id' => null,
    'options' => [],
    'value' => null,
    'default' => null,
    'size' => 'medium',
    'color' => 'primary',
    'fullWidth' => false,
    'disabled' => false,
    'yoyo' => false,
    'label' => null,
    'labelIcon' => null,
])

@php
    $id = $id ?? $name;

    $requestValue = request()->input($name);
    $currentValue = $requestValue !== null ? $requestValue : $value;

    if (is_bool($currentValue)) {
        $currentValue = $currentValue ? '1' : '0';
    }
    $currentValue = $currentValue !== null ? (string) $currentValue : null;

    $optionKeys = array_keys($options);
    if ($currentValue === null || !in_array($currentValue, array_map('strval', $optionKeys), true)) {
        $currentValue = count($optionKeys) > 0 ? (string) $optionKeys[0] : null;
    }

    $sizeClass = 'button-group--' . $size;
    $colorClass = 'button-group--' . $color;
    $widthClass = $fullWidth ? 'button-group--full-width' : '';
    $disabledClass = $disabled ? 'button-group--disabled' : '';
    $hasLabel = !empty($label);
@endphp

<div class="button-group-wrapper {{ $hasLabel ? 'button-group-wrapper--with-label' : '' }}">
    @if ($hasLabel)
        <span class="button-group__label">
            @if ($labelIcon)
                <x-icon path="{{ $labelIcon }}" class="button-group__label-icon" />
            @endif
            {{ $label }}
        </span>
    @endif

<div class="button-group {{ $sizeClass }} {{ $colorClass }} {{ $widthClass }} {{ $disabledClass }}"
     data-button-group
     data-default="{{ $default ?? $value }}"
     role="radiogroup"
     aria-labelledby="{{ $id }}-label">
    
    @foreach ($options as $optionValue => $option)
        @php
            $optionValue = (string) $optionValue;
            $isSelected = $currentValue === $optionValue;
            $optionLabel = $option['label'] ?? null;
            $icon = $option['icon'] ?? null;
            $tooltip = $option['tooltip'] ?? null;
            $hasOnlyIcon = $icon && !$optionLabel;
            $inputId = $id . '-' . $optionValue;
        @endphp

        <label class="button-group__option {{ $isSelected ? 'button-group__option--active' : '' }} {{ $hasOnlyIcon ? 'button-group__option--icon-only' : '' }}"
               for="{{ $inputId }}"
               @if ($tooltip)
                   data-tooltip="{{ $tooltip }}"
                   data-tooltip-pos="top"
               @endif>

            <input type="radio"
                   class="button-group__input"
                   id="{{ $inputId }}"
                   name="{{ $name }}"
                   value="{{ $optionValue }}"
                   {{ $isSelected ? 'checked' : '' }}
                   {{ $disabled ? 'disabled' : '' }}
                   @if ($yoyo) yoyo hx-trigger="change" @endif
                   aria-checked="{{ $isSelected ? 'true' : 'false' }}">

            <span class="button-group__content">
                @if ($icon)
                    <x-icon path="{{ $icon }}" class="button-group__icon" />
                @endif

                @if ($optionLabel)
                    <span class="button-group__option-label">{{ $optionLabel }}</span>
                @endif
            </span>
        </label>
    @endforeach
</div>
</div>
