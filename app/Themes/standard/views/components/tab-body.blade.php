@aware(['name' => null])

@php
    $name = preg_replace('/[^\w-]/', '_', $name);
@endphp

<div {{ $attributes->merge(['class' => 'tabs-content']) }} role="tabpanel" 
    aria-labelledby="tab-{{ $name }}" data-name="tab__{{ $name }}">
    {{ $slot }}
</div>
