@props([
    'name',
    'variant' => null, // null (stacked cards) | 'inline' (button group)
])

<div {{ $attributes->merge(['class' => 'radio-group' . ($variant === 'inline' ? ' radio-group--inline' : '')]) }}>
    <div class="radio-group__items">
        {{ $slot }}
    </div>
</div>
