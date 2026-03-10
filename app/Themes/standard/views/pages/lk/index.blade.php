@extends('flute::layouts.app')

@section('title')
    {{ !empty(page()->title) ? page()->title : __('lk.title') }}
@endsection

@push('content')
    <div class="container">
        @if ($isModal)
            <article class="lk-content" hx-swap="morph:outerHTML">
                @fragment('lk-card')
                    @yoyo('payment-form', ['isModal' => true])
                @endfragment
            </article>
        @else
            <div class="lk-layout">
                {{-- Main form --}}
                <main class="lk-main">
                    <article class="lk-content" hx-swap="morph:outerHTML">
                        @fragment('lk-card')
                            @yoyo('payment-form', ['isModal' => false])
                        @endfragment
                    </article>
                </main>

                {{-- Sidebar --}}
                <aside class="lk-sidebar">
                    {{-- Balance card --}}
                    <div class="lk-balance">
                        <div class="lk-balance__top">
                            <span class="lk-balance__label">{{ __('lk.your_balance') }}</span>
                        </div>
                        <div class="lk-balance__amount">
                            {{ number_format(user()->getCurrentUser()->balance, 2) }}
                            <span>{{ config('lk.currency_view', 'FC') }}</span>
                        </div>
                    </div>

                    {{-- Recent payments --}}
                    <div class="lk-history">
                        <div class="lk-history__head">
                            <span class="lk-history__title">{{ __('lk.history_title') }}</span>
                        </div>

                        @if (count($recentInvoices) > 0)
                            <div class="lk-history__list">
                                @foreach ($recentInvoices as $invoice)
                                    @php
                                        $gwName = $gatewayNames[$invoice->gateway] ?? $invoice->gateway;
                                        $statusClass = $invoice->isPaid ? 'is-paid' : 'is-pending';
                                        $statusText = $invoice->isPaid ? __('lk.status_paid') : __('lk.status_pending');
                                    @endphp
                                    <div class="lk-history__item">
                                        <div class="lk-history__left">
                                            <span class="lk-history__dot {{ $statusClass }}"></span>
                                            <span class="lk-history__info">
                                                <span class="lk-history__gateway">{{ $gwName }}</span>
                                                <span class="lk-history__meta">
                                                    <span class="lk-history__status {{ $statusClass }}">{{ $statusText }}</span>
                                                    <span class="lk-history__sep">&middot;</span>
                                                    <span class="lk-history__date">{{ carbon($invoice->createdAt)->setTimezone(new \DateTimeZone(config('app.timezone', 'UTC')))->diffForHumans() }}</span>
                                                </span>
                                            </span>
                                        </div>
                                        <span class="lk-history__amount {{ $statusClass }}">
                                            {{ $invoice->isPaid ? '+' : '' }}{{ number_format($invoice->originalAmount, 0) }}
                                            {{ $invoice->currency?->code ?? '' }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="lk-history__empty">
                                <x-icon path="ph.regular.receipt" />
                                <span>{{ __('lk.no_history') }}</span>
                            </div>
                        @endif
                    </div>
                </aside>
            </div>
        @endif
    </div>
@endpush
