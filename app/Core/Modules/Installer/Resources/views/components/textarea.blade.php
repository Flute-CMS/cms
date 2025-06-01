@props([
    'name' => '',
    'id' => null,
    'label' => null,
    'value' => null,
    'placeholder' => null,
    'required' => false,
])

@php
    $id = $id ?? $name;
    $inputClass = 'installer-input__field';
    
    if ($errors->has($name)) {
        $inputClass .= ' has-error';
    }
@endphp

<div class="input-wrapper @if ($errors->has($name)) has-error @endif">
    @if ($label)
        <label for="{{ $id }}" class="input__label">
            {{ $label }}

            @if ($required)
                <span class="installer-input__required">*</span>
            @endif
        </label>
    @endif

    <textarea name="{{ $name }}" id="{{ $id }}" class="{{ $inputClass }}" placeholder="{{ $placeholder }}" {{ $attributes }}>{{ $value }}</textarea>
</div>

