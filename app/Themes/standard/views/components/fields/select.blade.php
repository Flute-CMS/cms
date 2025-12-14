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
    'native' => false,
    'searchable' => null,
    'searchThreshold' => 6,
])

@php
    $hasError = $errors->has($name);
    $isNative = $native || $readOnly;
    $optionsCount = count($options);
    $enableSearch = $searchable === true || ($searchable === null && $optionsCount > $searchThreshold);
@endphp

<div class="select-wrapper">
    @if ($label)
        <label class="select__prefix" for="{{ $attributes->get('id', $name) }}">
            {{ $label }}
        </label>
    @endif

    <div class="select__field-container {{ $hasError ? 'has-error' : '' }}{{ $isNative ? ' select__field-container--native' : '' }}">
        <select 
            name="{{ $name }}" 
            id="{{ $attributes->get('id', $name) }}"
            class="select__field" 
            {{ $yoyo ? 'yoyo' : '' }} 
            @if ($readOnly) readonly disabled @endif
            @if (!$isNative) 
                data-tom-select
                data-placeholder="{{ $placeholder }}"
                data-allow-empty="{{ $allowEmpty ? 'true' : 'false' }}"
                data-allow-add="{{ $allowAdd ? 'true' : 'false' }}"
                data-searchable="{{ $enableSearch ? 'true' : 'false' }}"
            @endif
            @if (!empty($datalist)) list="datalist-{{ $attributes->get('id', $name) }}" @endif
            {{ $attributes->merge(['class' => 'select__field']) }}>
            @if ($allowEmpty)
                <option value="" @if(empty($value) || !isset($options[$value])) selected @endif>{{ $placeholder ?: __('def.select_option') }}</option>
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

        @if ($isNative)
            <span class="select__toggle-icon">
                <svg width="10" height="7" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="2" fill="none" fill-rule="evenodd" />
                </svg>
            </span>
        @endif
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
