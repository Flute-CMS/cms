@props([
    'title' => null,
    'subtitle' => null,
    'footerText' => null,
    'withoutPadding' => false,
    'headerClass' => '',
    'bodyClass' => '',
    'footerClass' => '',
])

<article {{ $attributes->merge(['class' => 'card']) }}>
    @if (isset($header))
        <header class="card-header {{ $headerClass }}">
            {{ $header }}
        </header>
    @elseif ($title || $subtitle)
        <header class="card-header {{ $headerClass }}">
            @if ($title)
                <h5 class="card-title">{{ $title }}</h5>
            @endif
            @if ($subtitle)
                <h6 class="card-subtitle">{{ $subtitle }}</h6>
            @endif
        </header>
    @endif

    <div @class(['card-body', $bodyClass, $withoutPadding ? 'withoutPadding' : ''])>
        {{ $slot }}
    </div>

    @if (isset($footer))
        <footer class="card-footer {{ $footerClass }}">
            {{ $footer }}
        </footer>
    @elseif ($footerText)
        <footer class="card-footer {{ $footerClass }}">
            {{ $footerText }}
        </footer>
    @endif
</article>
