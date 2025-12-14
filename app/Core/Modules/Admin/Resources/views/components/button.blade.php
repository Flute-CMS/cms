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

    // Yoyo actions should include the current screen inputs by default (toggles/selects/etc).
    // Allow explicit overrides via attributes (e.g. hx-include="none").
    // NOTE: use raw attributes array to reliably detect keys like "yoyo:post".
    $rawAttrs = method_exists($attributes, 'getAttributes') ? $attributes->getAttributes() : [];
    $hasYoyoPost = array_key_exists('yoyo:post', $rawAttrs);
    $hasHxInclude = array_key_exists('hx-include', $rawAttrs);
    if ($tag === 'button' && $hasYoyoPost && !$hasHxInclude) {
        // Include all inputs from current screen (works with htmx/yoyo serialization)
        $elementAttributes['hx-include'] = '#screen-container';
    }
@endphp

<{{ $tag }} {{ $attributes->merge($elementAttributes) }}>
    @if ($icon)
        <x-icon class="me-1" path="{{ $icon }}" />
    @endif
    <span class="btn-label">{{ $name ?? $slot }}</span>
    </{{ $tag }}>
