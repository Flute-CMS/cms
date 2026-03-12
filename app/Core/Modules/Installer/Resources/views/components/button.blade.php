@props([
    'type' => 'button',
    'variant' => 'primary',
    'disabled' => false,
    'class' => '',
    'href' => null,
    'size' => null,
])

@php
    $classes = 'btn';
    $classes .= ' btn--' . $variant;

    if ($size) {
        $classes .= ' btn--' . $size;
    }

    if ($class) {
        $classes .= ' ' . $class;
    }
@endphp

@if ($href)
    <a href="{{ $href }}" class="{{ $classes }}" {{ $attributes }}>
        <span class="btn__spinner"></span>
        <span class="btn__label">{{ $slot }}</span>
    </a>
@else
    <button type="{{ $type }}" class="{{ $classes }}" @if ($disabled) disabled @endif {{ $attributes }}>
        <span class="btn__spinner"></span>
        <span class="btn__label">{{ $slot }}</span>
    </button>
@endif
