@extends('flute::layouts.app')

@section('title')
    {{ !empty(page()->title) ? page()->title : __('lk.error.title') }}
@endsection

@push('content')
    <div class="h-100 container mt-5">
        <section class="status-container error">
            <header class="status-header">
                <h1>{{ __('lk.error.fail_payment') }}</h1>
            </header>

            <p class="status-text">{{ __('lk.error.fail_payment_desc') }}</p>

            <x-button href="{{ url('/') }}" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true"
                type="outline-accent">
                <x-icon path="ph.regular.arrow-left" />
                {{ __('def.back_home') }}
            </x-button>

            <x-icon path="ph.bold.x-circle-bold" class="status-icon" />
        </section>
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
