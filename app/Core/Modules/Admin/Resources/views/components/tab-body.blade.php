@aware(['name' => null])

@php
    $name = preg_replace('/[^\w-]/', '_', $name);
@endphp

<div {{ $attributes->merge(['class' => 'tabs-content']) }} data-name="tab__{{ $name }}">
    {{ $slot }}
</div>
