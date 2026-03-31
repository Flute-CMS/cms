@props([
    'type' => 'text',
    'name' => '',
    'value' => '',
    'default' => '',
    'prefix' => '',
    'mask' => '',
    'id' => '',
    'readOnly' => false,
    'postPrefix' => false,
    'toggle' => true,
    'withoutBottom' => false,
    'filePond' => false,
    'filePondOptions' => [],
    'datalist' => [],
    'defaultFile' => null,
    'yoyo' => false,
    'format' => null,
    'enableTime' => true,
    'multiple' => false,
    'iconPacks' => [],
])

@php
    $hasError = $errors->has($name);
    $inputId = $id ?: ($attributes->get('id', $name) ?: $name);
    $isDateType = $type === 'date' || $type === 'datetime' || $type === 'datetime-local';
@endphp

@if ($type === 'hidden')
    <input type="hidden" name="{{ $name }}" id="{{ $inputId }}" value="{{ $value }}" />
@elseif ($isDateType)
    {{-- DatePicker: standalone wrapper, not inside input__field-container --}}
    @php
        $isDateOnly     = $type === 'date';
        $isDatetimeLocal = $type === 'datetime-local';
        $fpEnableTime   = $isDateOnly ? false : ($isDatetimeLocal ? true : $enableTime);
        $fpFormat       = $format ?: ($isDatetimeLocal ? 'Y-m-d\\TH:i' : ($fpEnableTime ? 'Y-m-d H:i' : 'Y-m-d'));
        $fpConfig = [
            'enableTime' => $fpEnableTime,
            'time_24hr' => true,
            'dateFormat' => $fpFormat,
            'allowInput' => true,
        ];
        if ($default) $fpConfig['defaultDate'] = $default;
    @endphp
    <div class="datepicker-field" data-datepicker data-datepicker-config='@json($fpConfig)'>
        <div @class([
            'datepicker-field__input-wrap',
            'has-error' => $hasError,
            'is-disabled' => $readOnly,
        ])>
            <span class="datepicker-field__icon">
                <x-icon path="ph.regular.calendar-blank" />
            </span>
            <input type="text" name="{{ $name }}" id="{{ $inputId }}"
                value="{{ $value }}" class="datepicker-field__input"
                @if ($attributes->get('placeholder')) placeholder="{{ $attributes->get('placeholder') }}" @endif
                {{ $hasError ? 'aria-invalid=true' : '' }}
                @if ($readOnly) disabled @endif
                @if ($yoyo) hx-swap="morph:outerHTML transition:true" yoyo yoyo:trigger="input changed delay:500ms" @endif
                autocomplete="off" readonly />
            <button type="button" class="datepicker-field__clear" aria-label="Clear" style="display:none">
                <x-icon path="ph.bold.x-bold" />
            </button>
        </div>

        @error($name)
            <span class="input__error">{{ $message }}</span>
        @enderror
    </div>
@else
    @if ($type === 'file' && $filePond)
        <input type="hidden" name="{{ $name }}_clear" value="0" data-filepond-clear="{{ $name }}" />
    @endif
    <div class="input-wrapper">
        <div id="input-{{ $inputId }}" @class([
            'input__field-container',
            'input__field-container-readonly' => $readOnly,
            'has-error' => $hasError,
        ])>
            @if ($prefix)
                <span class="input__prefix">{{ $prefix }}</span>
            @endif

            @if ($type === 'file' && $filePond)
                <input type="file" name="{{ $name }}" id="{{ $inputId }}" class="filepond input__field"
                    data-default-file="{{ $defaultFile }}" data-input-mask="{{ $mask ?? '' }}"
                    data-file-pond-options='@json($filePondOptions)'
                    data-accept="{{ $attributes->get('accept', '') }}" @readonly($readOnly)
                    {{ $hasError ? 'aria-invalid=true' : '' }} @if ($multiple) multiple @endif
                    {{ $attributes->merge(['class' => 'filepond input__field']) }} />
            @elseif ($type === 'color')
                <div class="color-inline-header">
                    <div class="color-inline-swatch" data-input-id="{{ $inputId }}"
                        style="--swatch-color: {{ $value ?: '#42445A' }}"></div>

                    <input type="text" name="{{ $name }}" id="{{ $inputId }}"
                        value="{{ $value }}" {{ $hasError ? 'aria-invalid=true' : '' }} @readonly($readOnly)
                        data-color="{{ $value ?: '#42445A' }}"
                        data-color-inline="true"
                        placeholder="#000000"
                        @if ($yoyo) hx-swap="morph:outerHTML transition:true" yoyo yoyo:trigger="input changed delay:500ms" @endif
                        {{ $attributes->merge(['class' => 'input__field input__field-color']) }} />
                </div>
            @elseif ($type === 'icon')
                <div class="icon-input-container">
                    <div class="icon-input-preview" role="button" tabindex="0"
                        aria-label="{{ __('def.select_icon') }}">
                        @if ($value)
                            {!! app(\Flute\Core\Modules\Icons\Services\IconFinder::class)->loadFile($value) !!}
                        @endif
                    </div>

                    <input type="text" name="{{ $name }}" id="{{ $inputId }}"
                        value="{{ $value }}" {{ $hasError ? 'aria-invalid=true' : '' }} @readonly($readOnly)
                        data-icon-picker="true" data-icon-packs='@json($iconPacks)'
                        placeholder="{{ __('def.select_icon') }}"
                        @if ($yoyo) hx-swap="morph:outerHTML transition:true" yoyo yoyo:trigger="input changed delay:500ms" @endif
                        {{ $attributes->merge(['class' => 'input__field input__field-icon']) }} />

                    <button type="button" class="input__icon-picker-btn"
                        aria-label="{{ __('def.select_icon') }}">
                        <x-icon path="ph.regular.magnifying-glass" />
                    </button>
                </div>
            @else
                <input type="{{ $type }}" name="{{ $name }}" id="{{ $inputId }}"
                    data-input-mask="{{ $mask ?? '' }}" data-default="{{ $default }}"
                    {{ $hasError ? 'aria-invalid=true' : '' }} @readonly($readOnly)
                    @if (!empty($datalist)) list="datalist-{{ $inputId }}" @endif
                    value="{{ $value }}"
                    @if ($yoyo) hx-swap="morph:outerHTML transition:true" yoyo yoyo:trigger="input changed delay:500ms" @endif
                    {{ $attributes->class(['input__field-withPassword' => $type === 'password'])->merge(['class' => 'input__field']) }} />
            @endif

            @if ($type === 'password' && $toggle)
                <button type="button" onclick="togglePassword(event)" class="input__toggle-btn"
                    aria-label="Toggle Password Visibility">
                    <x-icon class="icon-eye" path="ph.regular.eye" />
                    <x-icon class="icon-eye-slash" path="ph.regular.eye-slash" style="display: none;" />
                </button>
            @endif

            @if ($postPrefix)
                <div class="input__postprefix">{!! $postPrefix !!}</div>
            @endif

            @if (!empty($datalist))
                <datalist id="datalist-{{ $inputId }}">
                    @foreach ($datalist as $item)
                        <option value="{{ $item }}"></option>
                    @endforeach
                </datalist>
            @endif
        </div>

        @if ($type === 'color')
            <div class="color-inline-picker is-collapsed" data-input-id="{{ $inputId }}"></div>
        @endif

        @error($name)
            <span class="input__error">{{ $message }}</span>
        @enderror
    </div>
@endif
