<x-card class="recent-payments" withoutPadding>
    <x-slot name="header">
        <div class="recent-payments-header">
            <h5>
                <span class="recent-payments-icon">
                    <x-icon path="ph.regular.currency-circle-dollar" />
                </span>
                {{ __('widgets.recent_payments') }}
            </h5>
            <small class="text-muted recent-payments-count">{{ count($payments) }}</small>
        </div>
    </x-slot>
    <div class="recent-payments-content" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">
        @if (!empty($payments))
            <div class="payment-list">
                @foreach ($payments as $payment)
                    <div class="payment-item">
                        <a href="{{ url('profile/' . $payment->user->getUrl()) }}" class="payment-user-wrapper"
                            data-user-card>
                            <div class="payment-avatar">
                                <img src="{{ asset($payment->user->avatar ?? config('profile.default_avatar')) }}"
                                    alt="{{ $payment->user->name }}" />
                            </div>
                            <div class="payment-info">
                                <div class="payment-user">
                                    <span>{{ $payment->user->name }}</span>
                                </div>
                                <div class="payment-date">
                                    {{ $payment->paidAt->format('d.m.Y H:i') }}
                                </div>
                            </div>
                        </a>
                        <div class="payment-meta">
                            <div class="payment-amount">
                                <span class="amount">{{ $payment->amount }}</span>
                                @if ($payment->currency)
                                    <span class="currency">{{ $payment->currency->code }}</span>
                                @endif
                            </div>
                            <div class="payment-gateway">
                                <span class="badge">{{ $payment->gateway }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="recent-payments-empty">
                <x-icon path="ph.regular.currency-circle-dollar" />
                <p>{{ __('widgets.no_payments') }}</p>
            </div>
        @endif
    </div>
</x-card>
