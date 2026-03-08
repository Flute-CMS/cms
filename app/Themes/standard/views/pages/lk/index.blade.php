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
                {{-- Sidebar --}}
                <aside class="lk-sidebar">
                    {{-- Balance --}}
                    <div class="lk-balance card">
                        <div class="lk-balance__label">{{ __('lk.your_balance') }}</div>
                        <div class="lk-balance__amount">
                            {{ number_format(user()->getCurrentUser()->balance, 2) }}
                            <span>{{ config('lk.currency_view', 'FC') }}</span>
                        </div>
                    </div>

                    {{-- Recent payments --}}
                    <div class="lk-history card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('lk.history_title') }}</h3>
                        </div>
                        <div class="card-body withoutPadding">
                            @if (count($recentInvoices) > 0)
                                <div class="lk-history__list">
                                    @foreach ($recentInvoices as $invoice)
                                        <div class="lk-history__item">
                                            <div class="lk-history__left">
                                                <span class="lk-history__status {{ $invoice->isPaid ? 'is-paid' : 'is-pending' }}">
                                                    @if ($invoice->isPaid)
                                                        <x-icon path="ph.bold.check" />
                                                    @else
                                                        <x-icon path="ph.bold.clock" />
                                                    @endif
                                                </span>
                                                <span class="lk-history__info">
                                                    <span class="lk-history__gateway">{{ $invoice->gateway }}</span>
                                                    <span class="lk-history__date">
                                                        {{ carbon($invoice->createdAt)->setTimezone(new \DateTimeZone(config('app.timezone', 'UTC')))->diffForHumans() }}
                                                    </span>
                                                </span>
                                            </div>
                                            <span class="lk-history__amount {{ $invoice->isPaid ? 'is-paid' : '' }}">
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
                    </div>
                </aside>

                {{-- Main form --}}
                <main class="lk-main">
                    <article class="lk-content" hx-swap="morph:outerHTML">
                        @fragment('lk-card')
                            @yoyo('payment-form', ['isModal' => false])
                        @endfragment
                    </article>
                </main>
            </div>
        @endif
    </div>
@endpush
