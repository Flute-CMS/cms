@php
    $inputId = $id ?: $name;
    $hasError = $errors->has($name);

    $config = [
        'enableTime' => $enableTime ?? false,
        'noCalendar' => $noCalendar ?? false,
        'time_24hr' => $time24hr ?? true,
        'mode' => $mode ?? 'single',
        'inline' => $inline ?? false,
        'allowInput' => $allowInput ?? true,
        'weekNumbers' => $weekNumbers ?? false,
    ];

    if (!empty($dateFormat)) $config['dateFormat'] = $dateFormat;
    if (!empty($altFormat)) {
        $config['altInput'] = true;
        $config['altFormat'] = $altFormat;
    }
    if (!empty($minDate)) $config['minDate'] = $minDate;
    if (!empty($maxDate)) $config['maxDate'] = $maxDate;
    if (!empty($defaultDate)) $config['defaultDate'] = $defaultDate;
    if (!empty($locale)) $config['locale'] = $locale;
@endphp

@if (is_callable($typeForm))
    {!! $typeForm(get_defined_vars()) !!}
@else
    @component($typeForm, compact('id', 'name', 'title', 'popover', 'attributes') + get_defined_vars())
        <div class="datepicker-field" data-datepicker data-datepicker-config='@json($config)'>
            <div @class([
                'datepicker-field__input-wrap',
                'has-error' => $hasError,
                'is-disabled' => $disabled ?? false,
            ])>
                <span class="datepicker-field__icon">
                    @if ($noCalendar ?? false)
                        <x-icon path="ph.regular.clock" />
                    @else
                        <x-icon path="ph.regular.calendar-blank" />
                    @endif
                </span>
                <input
                    type="text"
                    name="{{ $name }}"
                    id="{{ $inputId }}"
                    value="{{ $value }}"
                    class="datepicker-field__input"
                    @if ($placeholder) placeholder="{{ $placeholder }}" @endif
                    @if ($disabled ?? false) disabled @endif
                    @if ($hasError) aria-invalid="true" @endif
                    @if ($yoyo ?? false) hx-swap="morph:outerHTML transition:true" yoyo yoyo:trigger="input changed delay:500ms" @endif
                    autocomplete="off"
                    readonly
                />
                @if (!($inline ?? false))
                    <button type="button" class="datepicker-field__clear" aria-label="Clear" style="display:none">
                        <x-icon path="ph.bold.x-bold" />
                    </button>
                @endif
            </div>

            @error($name)
                <span class="input__error">{{ $message }}</span>
            @enderror
        </div>
    @endcomponent
@endif
