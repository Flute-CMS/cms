<x-card class="recent-payments">
    <x-slot name="header">
        <div class="recent-payments__header">
            <h5>{{ __('widgets.recent_payments') }}</h5>
            @if (!empty($payments))
                <span class="recent-payments__count">{{ count($payments) }}</span>
            @endif
        </div>
    </x-slot>

    @if (!empty($payments))
        <div class="recent-payments__list" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">
            @foreach ($payments as $payment)
                <a href="{{ url('profile/' . $payment->user->getUrl()) }}"
                    class="recent-payments__item"
                    data-user-card>
                    <div class="recent-payments__user">
                        <img class="recent-payments__avatar"
                            src="{{ asset($payment->user->avatar ?? config('profile.default_avatar')) }}"
                            alt="{{ $payment->user->name }}"
                            loading="lazy">
                        <div class="recent-payments__info">
                            <span class="recent-payments__name">{!! $payment->user->getDisplayName() !!}</span>
                            <span class="recent-payments__time">
                                {{ carbon($payment->paidAt)->setTimezone(new \DateTimeZone(config('app.timezone', 'UTC')))->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                    <div class="recent-payments__payment">
                        <span class="recent-payments__amount">
                            +{{ number_format($payment->amount, 0) }}
                            @if ($payment->currency)
                                <small>{{ $payment->currency->code }}</small>
                            @endif
                        </span>
                        <span class="recent-payments__gateway">{{ $payment->gateway }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="recent-payments__empty">
            <x-icon path="ph.regular.wallet" />
            <span>{{ __('widgets.no_payments') }}</span>
        </div>
    @endif
</x-card>
