@props([
    'active' => false,
    'name' => 'tab',
])
@php
    $name = preg_replace('/[^\w-]/', '_', $name);
    $active = filter_var($active, FILTER_VALIDATE_BOOLEAN);
@endphp

<div role="tabpanel" id="tab__{{ $name }}"
    {{ $attributes->merge(['class' => 'tab-content ' . ($active ? 'active' : '')]) }}>
    {{ $slot }}
</div>
