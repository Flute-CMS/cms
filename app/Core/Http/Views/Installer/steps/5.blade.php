@extends('Core/Http/Views/Installer/app.blade.php')

@section('title')
    {{ __('install.5.title') }}
@endsection

@push('header')
    @at('Core/Http/Views/Installer/assets/styles/pages/four.scss')
@endpush

@push('content')
    <div class="container-installer">
        <h1 class="first-title animate__animated">{{ __('install.5.card_head') }}</h1>
        <div class="card">
            <div class="card-header">
                <a href="{{ url('install/4') }}" class="back-btn">
                    <i class="ph ph-caret-left"></i>
                </a>
                <p>{{ __('install.5.card_head_desc') }}</p>
            </div>

            {!! $form !!}
        </div>
    </div>
    @btnInst(['text' => __('Продолжить'), 'id' => 'continue', 'disabled' => true])
@endpush

@push('footer')
    @at('Core/Http/Views/Installer/assets/js/five.js')
@endpush
