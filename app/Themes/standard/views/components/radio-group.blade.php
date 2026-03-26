@props([
    'name',
    'legend' => null,
    'variant' => null, // null (stacked cards) | 'inline' (button group)
])

<fieldset {{ $attributes->merge(['class' => 'radio-group' . ($variant === 'inline' ? ' radio-group--inline' : '')]) }}>
    @if ($legend)
        <legend class="radio-group__legend">{{ $legend }}</legend>
    @endif
    <div class="radio-group__items">
        {{ $slot }}
    </div>
</fieldset>
