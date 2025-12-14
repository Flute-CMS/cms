@props(['type' => 'primary', 'href' => '#'])

@php
    $baseClasses = 'link';
    $typeClasses = [
        'accent' => 'link-accent',
        'primary' => 'link-primary',
        'error' => 'link-error',
        'warning' => 'link-warning',
        'success' => 'link-success',
        'info' => 'link-info',
    ];
    $classes = $baseClasses . ' ' . ($typeClasses[(string) $type] ?? $typeClasses['primary']);
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
