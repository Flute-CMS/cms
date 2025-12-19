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

<div class="select-wrapper" @if ($yoyo) yoyo hx-trigger="change delay:50ms" @endif>
    @if ($label)
        <label class="select__prefix" for="{{ $attributes->get('id', $name) }}">
            {{ $label }}
        </label>
    @endif

    <div class="select__field-container {{ $hasError ? 'has-error' : '' }}" data-controller="select"
        data-select-placeholder="{{ $placeholder }}" data-select-allow-empty="{{ $allowEmpty }}"
        data-select-message-notfound="{{ __('No results found') }}"
        data-select-allow-add="{{ var_export($allowAdd, true) }}" data-select-message-add="{{ __('Add') }}">
        <select name="{{ $name }}" id="{{ $attributes->get('id', $name) }}" class="select__field" data-select
            @if ($readOnly) readonly @endif
            @if ($allowEmpty) data-allow-empty="true" @endif
            @if ($allowAdd) data-allow-add="true" @endif
            @if (!empty($datalist)) list="datalist-{{ $attributes->get('id', $name) }}" @endif
            data-initial-value="{{ json_encode($value) }}" {{ $attributes->merge(['class' => 'select__field']) }}>
            @if ($allowEmpty)
                <option value="" @if (empty($value) || !isset($options[$value])) selected @endif disabled>
                    {{ $placeholder ?: __('def.select_option') }}</option>
            @endif
            <!-- DEBUG SELECT: value_type="{{ gettype($value) }}" value_json="{{ json_encode($value) }}" raw_value="{{ is_string($value) || is_numeric($value) ? $value : '?' }}" -->
            @foreach ($options as $key => $option)
                <option value="{{ $key }}"
                    @if (is_array($value)) @if (in_array($key, $value)) selected
                        @elseif(isset($value[$key]) && $value[$key] == $option) selected @endif
                @elseif ((string) $key === (string) $value) selected @endif>
                    {{ $option }}
                </option>
            @endforeach

            {{ $slot }}
        </select>
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
