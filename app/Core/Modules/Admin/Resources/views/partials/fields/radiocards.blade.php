@props([
    'name',
    'id' => null,
    'options' => [],
    'value' => null,
    'columns' => 3,
    'size' => 'medium',
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
@endphp

<div class="radio-cards radio-cards--{{ $size }} radio-cards--cols-{{ $columns }} {{ $disabled ? 'radio-cards--disabled' : '' }}"
     role="radiogroup"
     aria-labelledby="{{ $id }}-label"
     style="--rc-cols: {{ $columns }}">

    @foreach ($options as $optionValue => $option)
        @php
            $optionValue = (string) $optionValue;
            $isSelected = $currentValue === $optionValue;
            $optionLabel = $option['label'] ?? null;
            $icon = $option['icon'] ?? null;
            $desc = $option['desc'] ?? null;
            $inputId = $id . '-' . $optionValue;
        @endphp

        <label class="radio-cards__item {{ $isSelected ? 'radio-cards__item--active' : '' }}"
               for="{{ $inputId }}">

            <input type="radio"
                   class="radio-cards__input"
                   id="{{ $inputId }}"
                   name="{{ $name }}"
                   value="{{ $optionValue }}"
                   {{ $isSelected ? 'checked' : '' }}
                   {{ $disabled ? 'disabled' : '' }}
                   @if ($yoyo) yoyo hx-trigger="change" @endif
                   aria-checked="{{ $isSelected ? 'true' : 'false' }}">

            @if ($icon)
                <span class="radio-cards__icon" aria-hidden="true">
                    <x-icon path="{{ $icon }}" />
                </span>
            @endif

            @if ($optionLabel || $desc)
                <span class="radio-cards__text">
                    @if ($optionLabel)
                        <span class="radio-cards__label">{{ $optionLabel }}</span>
                    @endif
                    @if ($desc)
                        <span class="radio-cards__desc">{{ $desc }}</span>
                    @endif
                </span>
            @endif
        </label>
    @endforeach
</div>
