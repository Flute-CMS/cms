@props([
    'name' => '',
    'scrollable' => true,
    'variant' => null, // 'pills' | 'underline' (default) | 'segment'
])

@php
    $name = preg_replace('/[^\w-]/', '_', $name);
    $scrollable = filter_var($scrollable, FILTER_VALIDATE_BOOLEAN);

    $variantClass = match ($variant) {
        'pills' => ' pills',
        'segment' => ' segment',
        default => '',
    };
@endphp

<div {{ $attributes->merge(['class' => 'tabs-container' . $variantClass . ($scrollable ? ' scrollable-tabs' : '')]) }}
    data-tabs-id="tab__{{ $name }}">
    <div class="tabs-nav-wrapper">
        <ul class="{{ $name }}-headings tabs-nav" role="tablist">
            {{ $headings }}
            @if (!$variant || $variant === 'underline')
                <div class="underline" hx-preserve></div>
            @endif
        </ul>
    </div>
    {{ $slot }}
</div>
