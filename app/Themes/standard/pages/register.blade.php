@extends(tt('layout.blade.php'))

@push('header')
    @at(tt('assets/styles/pages/auth.scss'))
@endpush

@section('title')
    {{ !empty(page()->title) ? page()->title : t('auth.registration.title') }}
@endsection

@push('auth_socials')
    @foreach ($social as $key => $item)
        <a href="{{ url('social/' . $key) }}" class="auth_social_item">
            {!! $item !!}
        </a>
    @endforeach
@endpush

@push('content')
    @navbar
    <div class="container">
        @navigation
        @breadcrumb
        @editor

        @stack('container')

        <div class="row justify-content-md-center">
            <div class="col-md-5">
                <h1 class="mb-3 mt-0 text-center">{{ t('auth.registration.title') }}</h1>
                <div class="auth_container mb-4">
                    <div class="auth_socials mb-4">
                        @stack('auth_socials')
                    </div>
                    @if (sizeof($social) > 0)
                        <div class="container_auth_line">
                            <span class="line"></span>
                            <p class="text">@t('auth.auth.via_login')</p>
                            <span class="line"></span>
                        </div>
                    @endif
                </div>
                @flash
                <div class="card">
                    {!! $form !!}
                    <div class="auth_footer mt-3">
                        <p>@t('auth.do_have_account')</p>
                        <a href="{{ url('login') }}">@t('auth.auth.title')</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endpush

@footer
