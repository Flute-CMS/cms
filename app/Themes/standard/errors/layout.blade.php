@extends(tt('layout.blade.php'))

@push('header')
    @at(tt('assets/styles/layouts/_error.scss'))
@endpush

@section('title')
    {{ $message ?? $code }}
@endsection

@push('content')
    <div class="error_container container">
        <div class="error_header"><b>{{ $code }}</b></div>
        <p class="error_text">{{ $message }}</p>
        <div class="d-flex flex-column align-items-center">
            @if ($code == 404)
                <img class="secret_img" src="@at(tt('assets/img/secrets/travolta.gif'))" />
            @endif
            @if ($code == 403)
                <img class="secret_arnold" src="@at(tt('assets/img/secrets/arnold.webp'))" />
            @endif
            <a class="btn btn--with-icon mt-4 outline" href="/" role="button">
                {{ __('def.back_home') }}
                <span class="btn__icon arrow"><i class="ph ph-arrow-right"></i></span>
            </a>
        </div>
    </div>
@endpush
