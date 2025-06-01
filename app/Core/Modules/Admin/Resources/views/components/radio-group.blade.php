@props(['name'])

<div {{ $attributes->merge(['class' => 'radio-group']) }}>
    {{ $slot }}
</div>
