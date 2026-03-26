@props([
    'type' => 'text',
    'name' => '',
    'id' => null,
    'label' => null,
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'class' => '',
    'pattern' => null,
    'autofocus' => false,
    'hint' => null,
    'mono' => false,
])

@php
    $id = $id ?? $name;
    $hasError = $errors->has($name);
    $inputClass = 'field__input';

    if ($mono) {
        $inputClass .= ' field__input--mono';
    }

    if ($class) {
        $inputClass .= ' ' . $class;
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

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $id }}"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        class="{{ $inputClass }}"
        @if ($required) required @endif
        @if ($disabled) disabled @endif
        @if ($autofocus) autofocus @endif
        @if ($pattern) pattern="{{ $pattern }}" @endif
        {{ $attributes }}
    >

    @if ($hint && !$hasError)
        <span class="field__hint">{{ $hint }}</span>
    @endif

    @error($name)
        <span class="field__error">{{ $message }}</span>
    @enderror
</div>
