@props([
    'name' => '',
    'id' => null,
    'label' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'searchable' => false,
    'allowEmpty' => false,
    'class' => '',
])

@php
    $id = $id ?? $name;
    $hasError = $errors->has($name);
    $selectClass = 'field__input';

    if ($class) {
        $selectClass .= ' ' . $class;
    }
@endphp

<div @class(['field', 'has-error' => $hasError])>
    @if ($label)
        <label for="{{ $id }}" class="field__label">
            {{ $label }}
            @if ($required)
                <span class="required">*</span>
            @endif
        </label>
    @endif

    <select
        name="{{ $name }}"
        id="{{ $id }}"
        class="{{ $selectClass }}"
        data-tom-select
        data-placeholder="{{ $placeholder ?? '' }}"
        data-allow-empty="{{ $allowEmpty ? 'true' : 'false' }}"
        data-searchable="{{ $searchable ? 'true' : 'false' }}"
        @if ($required) required @endif
        @if ($disabled) disabled @endif
        {{ $attributes }}
    >
        @if ($placeholder && $allowEmpty)
            <option value="" {{ is_null($selected) ? 'selected' : '' }}>{{ $placeholder }}</option>
        @endif

        @foreach ($options as $value => $optionLabel)
            <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }}>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>

    @error($name)
        <span class="field__error">{{ $message }}</span>
    @enderror
</div>
