@props([
    'name' => '',
    'label' => '',
    'options' => [],
    'yoyo' => false,
    'value' => '',
    'placeholder' => '',
    'allowEmpty' => false,
    'allowAdd' => false,
    'readOnly' => false,
    'datalist' => [],
    'toggle' => false,
])

@php
    $hasError = $errors->has($name);
@endphp

<div class="select-wrapper">
    @if ($label)
        <label class="select__prefix" for="{{ $attributes->get('id', $name) }}">
            {{ $label }}
        </label>
    @endif

    <div class="select__field-container {{ $hasError ? 'has-error' : '' }}" data-controller="select"
        data-select-placeholder="{{ $placeholder }}" data-select-allow-empty="{{ $allowEmpty }}"
        data-select-message-notfound="{{ __('No results found') }}"
        data-select-allow-add="{{ var_export($allowAdd, true) }}" data-select-message-add="{{ __('Add') }}">
        <select name="{{ $name }}" id="{{ $attributes->get('id', $name) }}"
            class="select__field" {{ $yoyo ? 'yoyo' : '' }} @if ($readOnly) readonly @endif
            @if (!empty($datalist)) list="datalist-{{ $attributes->get('id', $name) }}" @endif
            {{ $attributes->merge(['class' => 'select__field']) }}>
            @if ($allowEmpty)
                <option value="" @if(empty($value) || !isset($options[$value])) selected @endif disabled>{{ $placeholder ?: __('def.select_option') }}</option>
            @endif
            @foreach ($options as $key => $option)
                <option value="{{ $key }}"
                    @if (is_array($value) && in_array($key, $value)) selected
                    @elseif(isset($value[$key]) && $value[$key] == $option) selected
                    @elseif($key == $value) selected @endif>
                    {{ $option }}
                </option>
            @endforeach

            {{ $slot }}
        </select>

        <span class="select__toggle-icon">
            <svg width="10" height="7" xmlns="http://www.w3.org/2000/svg">
                <path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="2" fill="none" fill-rule="evenodd" />
            </svg>
        </span>
    </div>

    @if (!empty($datalist))
        <datalist id="datalist-{{ $attributes->get('id', $name) }}">
            @foreach ($datalist as $item)
                <option value="{{ $item }}"></option>
            @endforeach
        </datalist>
    @endif

    @error($name)
        <span class="select__error">{{ $message }}</span>
    @enderror
</div>
