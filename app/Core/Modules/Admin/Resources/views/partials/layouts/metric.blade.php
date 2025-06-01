<div class="metrics">
    @isset($title)
        <div class="metrics__title">
            {{ __($title) }}
        </div>
    @endisset
    <div class="metrics__grid">
        @foreach ($metrics as $key => $metric)
            <div class="metrics__item">
                <div class="metric">
                    <div class="metric__header">
                        @if($metric['icon'])
                            <div class="metric__icon">
                                <x-icon path="ph.regular.{{ $metric['icon'] }}" class="metric__icon-svg"></x-icon>
                            </div>
                        @endif
                        <span class="metric__label">{{ __($key) }}</span>
                    </div>
                    <div class="metric__content">
                        <div class="metric__main">
                            <div class="metric__value">
                                {{ is_array($metric['value']) ? $metric['value']['value'] : $metric['value'] }}
                            </div>
                            @if (isset($metric['value']['diff']) && $metric['value']['diff'] != 0)
                                <div class="metric__trend {{ (float) $metric['value']['diff'] < 0 ? 'metric__trend--down' : 'metric__trend--up' }}">
                                    <x-icon 
                                        path="ph.bold.{{ (float) $metric['value']['diff'] < 0 ? 'trend-down' : 'trend-up' }}-bold"
                                        class="metric__trend-icon"
                                    ></x-icon>
                                    <span>{{ round($metric['value']['diff'], 2) }}%</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
