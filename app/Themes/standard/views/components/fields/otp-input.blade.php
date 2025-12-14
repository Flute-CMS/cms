@props([
    'name' => 'otp',
    'length' => 6,
    'value' => '',
])

@php
    $hasError = $errors->has($name);
    $chars = str_split(str_pad($value, $length, ' '));
@endphp

<div class="otp-input-wrapper">
    <div class="otp-input {{ $hasError ? 'has-error' : '' }}" data-otp-length="{{ $length }}">
        @for ($i = 0; $i < $length; $i++)
            <input 
                type="text" 
                maxlength="1" 
                inputmode="numeric" 
                pattern="[0-9]" 
                autocomplete="one-time-code"
                class="otp-input__field" 
                data-otp-index="{{ $i }}"
                value="{{ trim($chars[$i] ?? '') }}"
                {{ $hasError ? 'aria-invalid=true' : '' }} />
        @endfor
    </div>
    <input type="hidden" name="{{ $name }}" id="{{ $name }}" value="{{ $value }}" {{ $attributes }} />

    @error($name)
        <span class="input__error">{{ $message }}</span>
    @enderror
</div>