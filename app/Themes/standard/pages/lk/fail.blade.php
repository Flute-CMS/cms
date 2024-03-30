@extends(tt('layout.blade.php'))

@push('header')
    @at(tt('assets/styles/pages/lk/status.scss'))
@endpush

@section('title')
    @t('lk.error.title')
@endsection

@push('content')
    <div class="container status_container">
        <div class="status_header error"><b>@t('lk.error.fail_payment')</b></div>
        <p class="status_text">@t('lk.error.fail_payment_desc')</p>
        <div class="d-flex flex-column align-items-center">
            <img class="secret_img" src="@at(tt('assets/img/secrets/walter.gif'))" />
            <a class="btn mt-4 btn--with-icon outline" href="/" role="button">
                {{ __('def.back_home') }}
                <span class="btn__icon arrow"><i class="ph ph-arrow-right"></i></span>
            </a>
        </div>
    </div>
@endpush
