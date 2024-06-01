@extends(tt('layout.blade.php'))

@push('header')
    @at(tt('assets/styles/pages/lk/status.scss'))
@endpush

@section('title')
    @t('lk.error.title')
@endsection

@push('content')
    <div class="status_container container">
        <div class="status_header error"><b>@t('lk.error.fail_payment')</b></div>
        <p class="status_text">@t('lk.error.fail_payment_desc')</p>
        <div class="d-flex flex-column align-items-center">
            <img class="secret_img" src="@at(tt('assets/img/secrets/walter.gif'))" />
            <a class="btn btn--with-icon mt-4 outline" href="/" role="button">
                {{ __('def.back_home') }}
                <span class="btn__icon arrow"><i class="ph ph-arrow-right"></i></span>
            </a>
        </div>
    </div>

    @if (config('lk.pay_in_new_window'))
        <script>
            window.opener.postMessage({
                paymentStatus: 'error'
            }, '{{ config("app.url") }}');

            window.close();
        </script>
    @endif
@endpush
