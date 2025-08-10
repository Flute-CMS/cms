@props([
    'label' => null,
    'value' => null,
    'icon' => null,
    'prefix' => null,
    'suffix' => null,
    'trend' => null,           // e.g. 12 or "-3.4%"
    'trendDirection' => null,  // 'up' | 'down' | null (auto if trend is numeric)
    'progress' => null,        // 0..100
    'hint' => null,            // small text below
])

@php
    $numericTrend = is_numeric($trend) ? (float) $trend : null;
    $finalTrendDirection = $trendDirection;
    if ($finalTrendDirection === null && $numericTrend !== null) {
        $finalTrendDirection = $numericTrend >= 0 ? 'up' : 'down';
    }
    $trendText = is_null($trend)
        ? null
        : (is_numeric($trend) ? ( ($numericTrend > 0 ? '+' : '') . $numericTrend ) : (string) $trend);
    $progressValue = is_null($progress) ? null : max(0, min(100, (int) $progress));
@endphp

<article {{ $attributes->merge(['class' => 'metric']) }}>
    @if ($icon || $label)
        <header class="metric__header">
            @if ($icon)
                <span class="metric__icon" aria-hidden="true">
                    <x-icon class="metric__icon-svg" :path="$icon" />
                </span>
            @endif
            @if ($label)
                <span class="metric__label">{{ $label }}</span>
            @endif
        </header>
    @endif

    <div class="metric__content">
        <div class="metric__main">
            <div class="metric__value">
                @if($prefix)<span class="metric__value-prefix">{{ $prefix }}</span>@endif
                <span class="metric__value-number">{{ $value }}</span>
                @if($suffix)<span class="metric__value-suffix">{{ $suffix }}</span>@endif
            </div>

            @if ($trendText)
                <span @class([
                    'metric__trend',
                    'metric__trend--up' => $finalTrendDirection === 'up',
                    'metric__trend--down' => $finalTrendDirection === 'down',
                ])>
                    @if ($finalTrendDirection === 'down')
                        <x-icon class="metric__trend-icon" path="ph.bold.arrow-down-right-bold" />
                    @else
                        <x-icon class="metric__trend-icon" path="ph.bold.arrow-up-right-bold" />
                    @endif
                    <span class="metric__trend-text">{{ $trendText }}</span>
                </span>
            @endif
        </div>

        @if (!is_null($progressValue))
            <div class="metric__progress" role="progressbar" aria-valuenow="{{ $progressValue }}" aria-valuemin="0" aria-valuemax="100">
                <div class="metric__progress-bar" style="width: {{ $progressValue }}%"></div>
            </div>
        @endif

        @if ($hint)
            <div class="metric__hint">{{ $hint }}</div>
        @endif

        <div class="metric__extra">{{ $slot }}</div>
    </div>
</article>


