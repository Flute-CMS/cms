@extends('flute::layouts.app')

@section('title')
    {{ !empty(page()->title) ? page()->title : __('lk.error.title') }}
@endsection

@push('content')
    <div class="container">
        <div class="status-page">
            <div class="status-icon error">
                <x-icon path="ph.bold.x-bold" />
            </div>

            <h1 class="status-title">{{ __('lk.error.fail_payment') }}</h1>
            <p class="status-desc">{{ __('lk.error.fail_payment_desc') }}</p>

            <div class="status-hints">
                <div class="status-hint">
                    <x-icon path="ph.regular.arrows-clockwise" />
                    <span>{{ __('lk.error.tip_retry') }}</span>
                </div>
                <div class="status-hint">
                    <x-icon path="ph.regular.credit-card" />
                    <span>{{ __('lk.error.tip_check') }}</span>
                </div>
                <div class="status-hint">
                    <x-icon path="ph.regular.headset" />
                    <span>{{ __('lk.error.tip_support') }}</span>
                </div>
            </div>

            <div class="status-actions">
                <x-button href="{{ url('/lk') }}" hx-boost="true" hx-target="#main"
                    hx-swap="outerHTML transition:true" type="accent">
                    {{ __('lk.error.try_again') }}
                </x-button>
                <x-button href="{{ url('/') }}" hx-boost="true" hx-target="#main"
                    hx-swap="outerHTML transition:true" type="outline-primary" size="small">
                    {{ __('def.back_home') }}
                </x-button>
            </div>
        </div>
    </div>

    @if (config('lk.pay_in_new_window'))
        <script>
            window.opener.postMessage({
                paymentStatus: 'error'
            }, '{{ config('app.url') }}');

            window.close();
        </script>
    @endif
@endpush
