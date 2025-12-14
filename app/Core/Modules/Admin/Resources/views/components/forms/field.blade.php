@props(['class' => '', 'id', 'label' => '', 'small' => '', 'popover' => null, 'required' => false])

<div {{ $attributes->merge(['class' => 'form-field ' . $class]) }}>
    @if ($label)
        <x-forms.label :popover="$popover" :required="$required">{!! $label !!}</x-forms.label>
    @endif

    {!! $slot !!}

    @if ($small)
        <x-fields.small>{!! $small !!}</x-fields.small>
    @endif
</div>
