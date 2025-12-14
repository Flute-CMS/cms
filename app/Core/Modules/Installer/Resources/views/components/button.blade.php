@props([
    'type' => 'button',
    'variant' => 'primary',
    'loading' => false,
    'disabled' => false,
    'class' => '',
    'href' => null,
    'hxPost' => null,
    'hxGet' => null,
    'hxTarget' => null
])

@php
    $classes = 'installer-button';
    
    if ($variant === 'secondary') {
        $classes .= ' installer-button--secondary';
    }

    if ($variant === 'primary') {
        $classes .= ' installer-button--primary';
    }
    
    if ($loading) {
        $classes .= ' installer-button--loading';
    }
    
    if ($class) {
        $classes .= ' ' . $class;
    }
    
    $attributes = $attributes->merge([
        'class' => $classes,
        'type' => $href ? null : $type,
        'disabled' => $disabled
    ]);
    
    if ($hxPost) {
        $attributes = $attributes->merge(['hx-post' => $hxPost]);
    }
    
    if ($hxGet) {
        $attributes = $attributes->merge(['hx-get' => $hxGet]);
    }
    
    if ($hxTarget) {
        $attributes = $attributes->merge(['hx-target' => $hxTarget]);
    }
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes }}>
        @if ($loading)
            <span class="installer-button__spinner"></span>
        @endif
        {{ $slot }}
    </a>
@else
    <button {{ $attributes }}>
        @if ($loading)
            <span class="installer-button__spinner"></span>
        @endif
        {{ $slot }}
    </button>
@endif
