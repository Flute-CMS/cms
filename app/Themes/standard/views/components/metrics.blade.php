@props([
    'title' => null,
    'cols' => null,
    'variant' => null, // null (unified card) | 'cards' (separate cards)
])

<section {{ $attributes->merge(['class' => 'metrics' . ($variant === 'cards' ? ' metrics--cards' : '')]) }}>
    @if ($title)
        <h4 class="metrics__title">{{ $title }}</h4>
    @endif
    <div class="metrics__grid" @if ($cols) style="--metrics-cols: {{ $cols }}" @endif>
        {{ $slot }}
    </div>
</section>
