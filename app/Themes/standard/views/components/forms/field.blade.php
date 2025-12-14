@props(['class' => '', 'id', 'label'])

<div {{ $attributes->merge(['class' => 'form-field ' . $class]) }}>
    @if (isset($label))
        {!! $label !!}
    @endif
    {{ $slot }}
</div>
