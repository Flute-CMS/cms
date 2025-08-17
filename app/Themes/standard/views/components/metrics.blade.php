@props([
    'title' => null,
])

<section {{ $attributes->merge(['class' => 'metrics']) }}>
    @if ($title)
        <h4 class="metrics__title">{{ $title }}</h4>
    @endif
    <div class="metrics__grid">
        {{ $slot }}
    </div>
</section>


