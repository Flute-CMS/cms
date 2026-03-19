@props([
    'label' => null,
    'value' => null,
    'icon' => null,
    'prefix' => null,
    'suffix' => null,
    'trend' => null,
    'trendDirection' => null,
    'trendLabel' => null,
    'progress' => null,
    'hint' => null,
    'color' => null, // accent color for marker: 'primary' | 'success' | 'warning' | 'error' | 'info' | null
    'size' => null, // 'sm' | null (default) | 'lg'
])

@php
    $numericTrend = is_numeric($trend) ? (float) $trend : null;
    $finalTrendDirection = $trendDirection;
    if ($finalTrendDirection === null && $numericTrend !== null) {
        $finalTrendDirection = $numericTrend >= 0 ? 'up' : 'down';
    }
    $trendText = is_null($trend)
        ? null
        : (is_numeric($trend)
            ? ($numericTrend > 0 ? '+' : '') . $numericTrend . '%'
            : (string) $trend);
    $progressValue = is_null($progress) ? null : max(0, min(100, (int) $progress));
@endphp

<article {{ $attributes->merge(['class' => 'metric' . ($size ? ' metric--' . $size : '') . ($color ? ' metric--' . $color : '')]) }}>
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

    <div class="metric__body">
        <div class="metric__row">
            <div class="metric__value">
                @if ($prefix)
                    <span class="metric__affix">{{ $prefix }}</span>
                @endif
                <span class="metric__number">{{ $value }}</span>
                @if ($suffix)
                    <span class="metric__affix metric__affix--after">{{ $suffix }}</span>
                @endif
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
                    <span>{{ $trendText }}</span>
                </span>
            @endif
        </div>

        @if (!is_null($progressValue))
            <div class="metric__progress" role="progressbar" aria-valuenow="{{ $progressValue }}" aria-valuemin="0"
                aria-valuemax="100">
                <div class="metric__progress-fill" style="width: {{ $progressValue }}%"></div>
            </div>
        @endif

        @if ($hint || $trendLabel)
            <p class="metric__hint">{{ $hint ?? $trendLabel }}</p>
        @endif

        @if ($slot->isNotEmpty())
            <div class="metric__extra">{{ $slot }}</div>
        @endif
    </div>
</article>
