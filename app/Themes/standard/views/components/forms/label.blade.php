@props(['for' => null, 'required' => false])

<label for="{{ $for }}" {{ $attributes->class(['form__label', 'form__label-required' => $required]) }}>
    {{ $slot }}
</label>
