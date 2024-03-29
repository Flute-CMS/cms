@extends('Core/Http/Views/Installer/app.blade.php')

@section('title')
    {{ __('install.3.title') }}
@endsection

@push('header')
    @at('Core/Http/Views/Installer/assets/styles/pages/third.scss')
@endpush

@push('content')
    <div class="container-installer">
        <h1 class="first-title animate__animated">{{ __('install.3.card_head') }}</h1>
        <div class="card">
            <div class="card-header">
                <a href="{{ url('install/2') }}" class="back-btn">
                    <i class="ph ph-caret-left"></i>
                </a>
                <p>{{ __('install.3.card_head_desc') }}</p>
            </div>

            {!! $form !!}

            <button form="form" class="check_data" data-correct="{{ __('install.3.data_correct') }}" data-default=" {{ __('install.3.check_data') }}">
                {{ __('install.3.check_data') }}
            </button>
        </div>
    </div>
    @btnInst(['text' => __('Продолжить'), 'id' => 'continue', 'disabled' => true])
@endpush

@push('footer')
    @at('Core/Http/Views/Installer/assets/js/third.js')
@endpush
