@props([
    'type' => 'primary',
    'size' => 'medium',
    'disabled' => false,
    'withLoading' => false,
    'isLink' => false,
    'submit' => false,
    'href' => null,
    'icon' => null,
    'swap' => null,
    'confirm' => null,
    'confirmType' => 'error',
    'baseClasses' => 'btn',
])

@php
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
    $classes = implode(' ', [
        $baseClasses,
        $typeClasses[$type] ?? $typeClasses['primary'],
        $sizeClasses[$size] ?? $sizeClasses['medium'],
    ]);

    $isLink = $isLink || !empty($href);
    $elementAttributes = ['class' => $classes];

    if ($isLink) {
        $tag = 'a';
        $elementAttributes['href'] = $href ?? '#';
        if ($href) {
            $elementAttributes['hx-boost'] = 'false';
            $elementAttributes['hx-trigger'] = 'none';
        }
        if ($disabled) {
            $elementAttributes['aria-disabled'] = 'true';
            $elementAttributes['class'] .= ' disabled';
        }
    } else {
        $tag = 'button';
        $elementAttributes['type'] = $submit ? 'submit' : 'button';
        if ($disabled) {
            $elementAttributes['disabled'] = true;
        }
    }

    if ($withLoading) {
        $elementAttributes['data-loading-aria-busy'] = 'true';
    }

    if ($swap) {
        $elementAttributes['hx-trigger'] = 'none';
        $elementAttributes['hx-boost'] = 'true';
        $elementAttributes['hx-target'] = '#main';
        $elementAttributes['hx-swap'] = 'outerHTML transition:true';
    }

    if ($confirm) {
        $elementAttributes['hx-flute-confirm'] = $confirm;
        $elementAttributes['hx-flute-confirm-type'] = $confirmType;
        $elementAttributes['hx-trigger'] = 'confirmed';
    }
@endphp

<{{ $tag }} {{ $attributes->merge($elementAttributes) }}>
    @if ($icon)
        <x-icon class="me-1" path="{{ $icon }}" />
    @endif
    {{ $name ?? $slot }}
    </{{ $tag }}>
