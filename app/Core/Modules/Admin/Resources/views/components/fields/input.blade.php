@props([
    'type' => 'text',
    'name' => '',
    'value' => '',
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
    $inputId = $attributes->get('id', $name) ?: $name;
@endphp

@if ($type === 'hidden')
    <input type="hidden" name="{{ $name }}" id="{{ $inputId }}" value="{{ $value }}" />
@else
    <div class="input-wrapper">
        <div
            {{ $attributes->class(['input__field-container-readonly' => $readOnly, 'has-error' => $hasError])->merge(['class' => 'input__field-container']) }}>
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
                {{-- @elseif ($type === 'color')
            <input type="text" name="{{ $name }}" id="{{ $inputId }}"
                value="{{ $value }}" {{ $hasError ? 'aria-invalid=true' : '' }}
                @readonly($readOnly) data-color="{{ $value ?: '#42445A' }}"
                @if ($yoyo) hx-swap="morph:outerHTML transition:true" yoyo yoyo:trigger="input changed delay:500ms" @endif
                {{ $attributes->merge(['class' => 'input__field input__field-color']) }} />
            <div class="color-picker-container">
                <div class="color-preview" data-color-value="{{ $value ?: '#42445A' }}"></div>
            </div> --}}
            @elseif ($type === 'datetime')
                <input type="text" name="{{ $name }}" id="{{ $inputId }}" value="{{ $value }}"
                    {{ $hasError ? 'aria-invalid=true' : '' }} @readonly($readOnly)
                    @if ($format) data-format="{{ $format }}" @endif
                    data-enable-time="{{ $enableTime ? 'true' : 'false' }}"
                    @if ($yoyo) hx-swap="morph:outerHTML transition:true" yoyo yoyo:trigger="input changed delay:500ms" @endif
                    {{ $attributes->merge(['class' => 'input__field input__field-datetime']) }} />
            @elseif ($type === 'icon')
                <div class="icon-input-container">
                    <div class="icon-input-preview">
                        @if($value)
                            {!! app(\Flute\Core\Modules\Icons\Services\IconFinder::class)->loadFile($value) !!}
                        @endif
                    </div>
                    <input type="text" name="{{ $name }}" id="{{ $inputId }}"
                        value="{{ $value }}" {{ $hasError ? 'aria-invalid=true' : '' }} @readonly($readOnly)
                        data-icon-picker="true" data-icon-packs='@json($iconPacks)'
                        @if ($yoyo) hx-swap="morph:outerHTML transition:true" yoyo yoyo:trigger="input changed delay:500ms" @endif
                        {{ $attributes->merge(['class' => 'input__field input__field-icon']) }} />
                </div>
            @else
                <input type="{{ $type }}" name="{{ $name }}" id="{{ $inputId }}"
                    data-input-mask="{{ $mask ?? '' }}" {{ $hasError ? 'aria-invalid=true' : '' }} @readonly($readOnly)
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

        @error($name)
            <span class="input__error">{{ $message }}</span>
        @enderror
    </div>
@endif
