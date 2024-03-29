@extends('Core/Http/Views/Installer/app.blade.php')

@section('title')
    {{ __('install.7.title') }}
@endsection

@push('header')
    @at('Core/Http/Views/Installer/assets/styles/pages/five.scss')
@endpush

@push('content')
    <div class="container-installer"  data-finish="1">
        <h1 class="first-title animate__animated">{{ __('install.7.card_head') }}</h1>
        <div class="card">
            <div class="card-header">
                <a href="{{ url('install/6') }}" class="back-btn">
                    <i class="ph ph-caret-left"></i>
                </a>
                <p>{{ __('install.7.card_head_desc') }}</p>
            </div>

            {{-- <img class="tip_example" src="@at('Core/Http/Views/Installer/assets/img/tip_example.png')" alt="" loading="lazy"> --}}

            <form id="form">
                <div class="radio_button_block" style="margin-top: 10px">
                    <input type="radio" id="on" name="share" checked>
                    <label for="on">@t('install.7.yes')</label>
                </div>
                <div class="radio_button_block">
                    <input type="radio" id="off" name="share">
                    <label for="off">@t('install.7.no')</label>
                </div>
            </form>
        </div>
    </div>
    @btnInst(['text' => __('Завершить установку'), 'id' => 'continue'])
@endpush

@push('footer')
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.3.1"></script>
@endpush
