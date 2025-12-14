@extends('flute::layouts.app')

@section('title')
    {{ !empty(page()->title) ? page()->title : __('lk.success.title') }}
@endsection

@push('content')
    <div class="h-100 container mt-5">
        <section class="status-container success">
            <header class="status-header">
                <h1>{{ __('lk.success.success_payment') }}</h1>
            </header>

            <p class="status-text">{{ __('lk.success.success_payment_desc') }}</p>

            <x-button href="{{ url('/') }}" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true"
                type="outline-accent">
                <x-icon path="ph.regular.arrow-left" />
                {{ __('def.back_home') }}
            </x-button>

            <x-icon path="ph.bold.check-circle-bold" class="status-icon" />
        </section>
    </div>

    @if (config('lk.pay_in_new_window'))
        <script>
            window.opener.postMessage({
                paymentStatus: 'success'
            }, '{{ config('app.url') }}');

            window.close();
        </script>
    @endif
@endpush

@push('scripts')
    <script src="@asset('assets/js/libs/confetti.js')" defer></script>

    <script defer>
        $(() => {
            let count = 200;
            let defaults = {
                origin: {
                    y: 0.7
                }
            };

            function fire(particleRatio, opts) {
                confetti(Object.assign({}, defaults, opts, {
                    particleCount: Math.floor(count * particleRatio)
                }));
            }

            function startConfetti() {
                fire(0.25, {
                    spread: 26,
                    startVelocity: 55,
                });
                fire(0.2, {
                    spread: 60,
                });
                fire(0.35, {
                    spread: 100,
                    decay: 0.91,
                    scalar: 0.8
                });
                fire(0.1, {
                    spread: 120,
                    startVelocity: 25,
                    decay: 0.92,
                    scalar: 1.2
                });
                fire(0.1, {
                    spread: 120,
                    startVelocity: 45,
                });
            }

            startConfetti();
        });
    </script>
@endpush
