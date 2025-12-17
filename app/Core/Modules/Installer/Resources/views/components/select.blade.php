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

<div class="select-wrapper @if ($errors->has($name)) has-error @endif">
    @if ($label)
        <label for="{{ $id }}" class="select__prefix">
            {{ $label }}
            @if ($required)
                <span class="installer-input__required">*</span>
            @endif
        </label>
    @endif
    
    <div class="select__field-container @if ($errors->has($name)) has-error @endif">
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
    </div>
    
    @error($name)
        <span class="select__error">{{ $message }}</span>
    @enderror
</div> 