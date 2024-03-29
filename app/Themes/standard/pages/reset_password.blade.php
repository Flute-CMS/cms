@extends(tt('layout.blade.php'))

@push('header')
    @at(tt('assets/css/pages/reset.scss'))
@endpush

@section('title')
    {{ !empty(page()->title) ? page()->title : t('auth.reset.title') }}
@endsection

@push('content')
    @navbar
    <div class="container">
        @navigation
        @breadcrumb
        @editor

        @stack('container')

        <div class="row justify-content-md-center">
            <div class="col-md-5">
                <h1 class="mt-5 mb-4 text-center">{{ t('auth.reset.title') }}</h1>
                @flash
                <div class="card">
                    {!! $form !!}
                </div>
            </div>
        </div>
    </div>
@endpush

@footer