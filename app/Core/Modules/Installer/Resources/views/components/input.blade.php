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
    'autofocus' => false
])

@php
    $id = $id ?? $name;
    $inputClass = 'installer-input__field';
    
    if ($errors->has($name)) {
        $inputClass .= ' has-error';
    }
    
    if ($class) {
        $inputClass .= ' ' . $class;
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
    
    @error($name)
        <span class="installer-input__error">{{ $message }}</span>
    @enderror
</div> 