@extends(tt('layout.blade.php'))

@push('header')
    @at(tt('assets/styles/pages/lk/status.scss'))
@endpush

@section('title')
    @t('lk.success.title')
@endsection

@push('content')
    <div class="status_container container">
        <div class="status_header"><b>@t('lk.success.sucess_payment')</b></div>
        <p class="status_text">@t('lk.success.sucess_payment_desc')</p>
        <a class="btn btn--with-icon mt-4 outline" href="/" role="button">
            {{ __('def.back_home') }}
            <span class="btn__icon arrow"><i class="ph ph-arrow-right"></i></span>
        </a>
    </div>

    @if (config('lk.pay_in_new_window'))
        <script>
            window.opener.postMessage({
                paymentStatus: 'success'
            }, '{{ config("app.url") }}');

            window.close();
        </script>
    @endif
@endpush

@push('footer')
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.3.1"></script>

    <script>
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
    </script>
@endpush
