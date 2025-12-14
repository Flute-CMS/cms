@props([
    'name' => '',
    'id' => null,
    'label' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'class' => ''
])

@php
    $id = $id ?? $name;
    $selectClass = 'installer-select__field';
    
    if ($errors->has($name)) {
        $selectClass .= ' has-error';
    }
    
    if ($class) {
        $selectClass .= ' ' . $class;
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
    
    <select 
        name="{{ $name }}"
        id="{{ $id }}"
        class="{{ $selectClass }}"
        @if ($required) required @endif
        @if ($disabled) disabled @endif
        {{ $attributes }}
    >
        @if ($placeholder)
            <option value="" disabled {{ is_null($selected) ? 'selected' : '' }}>{{ $placeholder }}</option>
        @endif
        
        @foreach ($options as $value => $label)
            <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </select>
    
    @error($name)
        <span class="installer-input__error">{{ $message }}</span>
    @enderror
</div> 