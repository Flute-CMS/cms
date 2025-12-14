@props([
    'active' => false,
    'name' => 'tab',
])

@php
    $name = preg_replace('/[^\w-]/', '_', $name);
    $active = filter_var($active, FILTER_VALIDATE_BOOLEAN);
@endphp

<div id="tab__{{ $name }}" role="tabpanel" aria-labelledby="tab-{{ $name }}"
    {{ $attributes->merge(['class' => 'tab-content ' . ($active ? 'active' : ''), 'data-tab-id' => $name]) }} tabindex="0">
    {{ $slot }}
</div>
