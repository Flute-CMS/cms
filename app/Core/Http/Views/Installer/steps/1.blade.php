@extends('Core/Http/Views/Installer/app.blade.php')

@section('title')
    {{ __('install.1.title') }}
@endsection

@push('header')
    @at('Core/Http/Views/Installer/assets/styles/pages/first.scss')
@endpush

@push('content')
    <div class="welcome-overlay">
        <div class="welcome-div">
            <h1 class="welcome-text animate__animated"><span class="text-content animate__animated"></span></h1>
        </div>
        <div class="click-button animate__animated">
            <p></p>
            <i class="ph-arrow-right"></i>
        </div>
    </div>
    <div class="container-installer">
        <h1 class="first-title animate__animated">Выберите язык</h1>

        <div class="language-buttons">
            @foreach ($app->get('lang.available') as $item)
                <button class="lang-button" data-lang="{{ $item }}">
                    <img src="{{ url('assets/img/langs/' . $item . '.svg') }}" alt="" loading="lazy">
                    {{ __('langs.'.$item) }}
                </button>
            @endforeach
        </div>

        @btnInst(['text' => __('def.continue'), 'disabled' => true])
    </div>
@endpush

@push('footer')
    @at('Core/Http/Views/Installer/assets/js/first.js')
@endpush
