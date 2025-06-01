@props(['for' => '', 'required' => false, 'popover' => null])

<label @if ($for) for="{{ $for }}" @endif
    {{ $attributes->class(['form__label', 'form__label-required' => $required]) }}>
    {{ $slot }}
    @if ($popover)
        <x-popover :content="$popover" />
    @endif
</label>
