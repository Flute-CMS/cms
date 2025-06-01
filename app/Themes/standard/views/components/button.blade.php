@props([
    'type' => 'primary',
    'size' => 'medium',
    'disabled' => false,
    'withLoading' => false,
    'isLink' => false,
    'submit' => false,
    'href' => null,
    'swap' => false,
    'swapTarget' => '#main',
    'swapSwap' => 'outerHTML transition:true',
])

@php
    $baseClasses = 'btn';
    $typeClasses = [
        'accent' => 'btn-accent',
        'primary' => 'btn-primary',
        'error' => 'btn-error',
        'warning' => 'btn-warning',
        'success' => 'btn-success',
        'outline-accent' => 'btn-outline-accent',
        'outline-primary' => 'btn-outline-primary',
        'outline-error' => 'btn-outline-error',
        'outline-warning' => 'btn-outline-warning',
        'outline-success' => 'btn-outline-success',
    ];
    $sizeClasses = [
        'tiny' => 'btn-tiny',
        'small' => 'btn-small',
        'medium' => 'btn-medium',
        'large' => 'btn-large',
    ];
    $classes =
        $baseClasses .
        ' ' .
        ($typeClasses[(string) $type] ?? $typeClasses['primary']) .
        ' ' .
        ($sizeClasses[(string) $size] ?? $sizeClasses['medium']);
@endphp

@if ($isLink || (isset($href) && !empty($href)))
    <a @if ($swap) hx-boost="true" hx-target="{{ $swapTarget }}" hx-swap="{{ $swapSwap }}" @endif
        {{ $attributes->merge(['class' => $classes, 'role' => $isLink ? 'button' : null, 'aria-disabled' => $disabled ? 'true' : 'false']) }}
        @if ($href) href="{{ $href }}" @endif
        @if ($withLoading) data-loading-aria-busy @endif
        @if ($disabled) tabindex="-1" aria-disabled="true" @endif>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['class' => $classes]) }} type="{{ $submit ? 'submit' : 'button' }}"
        @disabled($disabled) @if ($withLoading) data-loading-aria-busy @endif>
        {{ $slot }}
    </button>
@endif
