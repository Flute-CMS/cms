@props([
    'name' => '',
    'label' => '',
    'value' => '',
    'rows' => 5,
    'placeholder' => '',
    'readOnly' => false,
    'withoutBottom' => false,
])

@php
    $hasError = $errors->has($name);
@endphp

<div class="textarea-wrapper">
    @if ($label)
        <label class="textarea__prefix" for="{{ $attributes->get('id', $name) }}">
            {{ $label }}
        </label>
    @endif

    <div class="textarea__field-container {{ $hasError ? 'has-error' : '' }}">
        <textarea name="{{ $name }}" id="{{ $attributes->get('id', $name) }}" class="textarea__field"
            rows="{{ $rows }}" placeholder="{{ $placeholder }}" @if ($readOnly) readonly @endif
            {{ $attributes->merge(['class' => 'textarea__field']) }}>{{ $value }}</textarea>
    </div>

    @error($name)
        <span class="textarea__error">{{ $message }}</span>
    @enderror
</div>
