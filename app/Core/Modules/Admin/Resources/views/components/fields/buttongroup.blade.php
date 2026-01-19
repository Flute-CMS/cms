@props([
    'name',
    'id' => null,
    'options' => [],
    'value' => null,
    'size' => 'medium',
    'color' => 'primary',
    'fullWidth' => false,
    'disabled' => false,
    'yoyo' => false,
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
@endphp

<div class="button-group {{ $sizeClass }} {{ $colorClass }} {{ $widthClass }} {{ $disabledClass }}"
     data-button-group
     role="radiogroup"
     aria-labelledby="{{ $id }}-label">
    
    @foreach ($options as $optionValue => $option)
        @php
            $optionValue = (string) $optionValue;
            $isSelected = $currentValue === $optionValue;
            $label = $option['label'] ?? null;
            $icon = $option['icon'] ?? null;
            $tooltip = $option['tooltip'] ?? null;
            $hasOnlyIcon = $icon && !$label;
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
                
                @if ($label)
                    <span class="button-group__label">{{ $label }}</span>
                @endif
            </span>
        </label>
    @endforeach
</div>
