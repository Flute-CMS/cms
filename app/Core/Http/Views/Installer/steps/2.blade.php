@extends('Core/Http/Views/Installer/app.blade.php')

@section('title')
    {{ __('install.2.title') }}
@endsection

@push('header')
    @at('Core/Http/Views/Installer/assets/styles/pages/second.scss')
@endpush

@push('content')
    <div class="container-installer bigger">
        <h1 class="first-title animate__animated">{{ __('install.2.card_head') }}</h1>
        <div class="card">
            <div class="card-header">
                <a href="{{ url('install/1') }}" class="back-btn">
                    <i class="ph ph-caret-left"></i>
                </a>
                <p>{{ __('install.2.card_head_desc') }}</p>
            </div>

            <div class="row">
                <div class="col-md-6 exts_name">
                    @t('install.2.other')
                </div>
                <a href="#" class="col-md-6 exts_name link">
                    @t('install.2.php_exts')
                </a>
            </div>

            <div class="row gx-3">
                <div class="col-md-6 exts_list">
                    <div class="row gy-3 gx-0">
                        <div
                            class="col-md-12 ext @if ($reqs['php_version']['current'] < 8) {{ $reqs['php_version']['required'] ? 'loaded' : 'disabled' }} @else recommended @endif">
                            <div class="ext_icon">
                                @if ($reqs['php_version']['required'] && $reqs['php_version']['current'] < 8)
                                    <i class="ph-bold ph-check"></i>
                                @elseif($reqs['php_version']['current'] > 8)
                                    <i class="ph ph-warning"></i>
                                @else
                                    <i class="ph ph-x-circle"></i>
                                @endif
                            </div>
                            <div class="ext_name">
                                @if ($reqs['php_version']['required'] && $reqs['php_version']['current'] < 8)
                                    <span class="status-label">@t('install.2.all_good')</span>
                                @elseif($reqs['php_version']['current'] > 8)
                                    <span class="status-label">@t('install.2.may_unstable')</span>
                                @else
                                    <span class="status-label">@t('install.2.need_to_install')</span>
                                @endif
                                <p>PHP: {{ $reqs['php_version']['current'] }}</p>
                            </div>
                        </div>
                        <div class="col-md-12 ext {{ $reqs['opcache_enabled']['required'] ? 'loaded' : 'recommended' }}">
                            <div class="ext_icon">
                                @if ($reqs['opcache_enabled']['required'])
                                    <i class="ph-bold ph-check"></i>
                                @else
                                    <i class="ph ph-warning"></i>
                                @endif
                            </div>
                            <div class="ext_name">
                                <span class="status-label">{{ $reqs['opcache_enabled']['required'] ? __('install.2.all_good') : __('install.2.may_installed') }}</span>
                                <p>OPCache: {{ $reqs['opcache_enabled']['current'] ? __('install.on') : __('install.off') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 exts_list">
                    <div class="row gy-3 gx-0">
                        @foreach ($exts['list'] as $key => $ext)
                            <div class="col-md-12 ext {{ $ext['type'] }}">
                                <div class="ext_icon">
                                    @if ($ext['type'] === 'loaded')
                                        <i class="ph-bold ph-check"></i>
                                    @elseif($ext['type'] === 'recommended')
                                        <i class="ph ph-warning"></i>
                                    @else
                                        <i class="ph ph-x-circle"></i>
                                    @endif
                                </div>
                                <div class="ext_name">
                                    @if ($ext['type'] === 'loaded')
                                        <span class="status-label">@t('install.2.all_good')</span>
                                    @elseif($ext['type'] === 'recommended')
                                        <span class="status-label">@t('install.2.may_installed')</span>
                                    @else
                                        <span class="status-label">@t('install.2.need_to_install')</span>
                                    @endif
                                    <p>{{ $key }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        @btnInst([
            'text' => $reqs['php_version']['required'] && $exts['bad'] === 0 ? __('install.next') : __('install.2.req_not_completed'),
            'disabled' => !($reqs['php_version']['required'] && $exts['bad'] === 0),
            'id' => 'continue',
        ])
    </div>
@endpush

@push('footer')
    @at('Core/Http/Views/Installer/assets/js/second.js')
@endpush
