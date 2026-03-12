<!DOCTYPE html>
<html lang="{{ strtolower(app()->getLang()) }}" data-theme="dark">

@php
    $title = __('install.title');
@endphp

<head hx-head="append">
    <title>
        @yield('title', $title)

        @hasSection('title')
            - {{ $title }}
        @endif
    </title>

    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport"
        content="minimum-scale=1, initial-scale=1, width=device-width, shrink-to-fit=no, user-scalable=no, viewport-fit=cover">
    <meta name="view-transition" content="same-origin">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="htmx-config" content='{
        "defaultFocusScroll": false,
        "scrollIntoViewOnBoost": false,
        "refreshOnHistoryMiss": true,
        "historyCacheSize": 0
    }'>
    <meta name="site_url" content="{{ config('app.url') }}">

    @stack('head')

    @if (isset($sections['head']))
        {!! $sections['head'] !!}
    @endif

    <link rel="icon" type="image/x-icon" href="@asset('favicon.ico')">
    <link rel="canonical" href="{{ url()->current() }}">

    @stack('styles')

    @if (isset($sections['styles']))
        {!! $sections['styles'] !!}
    @endif

    @if (request()->htmx()->isHtmxRequest())
        @stack('scripts')

        @if (isset($sections['scripts']))
            {!! $sections['scripts'] !!}
        @endif
    @endif

    @if (! request()->htmx()->isHtmxRequest())
        <link rel="stylesheet" href="@asset('assets/fonts/manrope/manrope.css')">
        <link rel="stylesheet" href="@asset('animate')" type='text/css'>
        <link rel="stylesheet" href="@asset('grid')" type='text/css'>

        @at(path('app/Core/Modules/Installer/Resources/assets/sass/installer.scss'))

        <script src="@asset('assets/js/htmx/core.js')"></script>
        <script src="@asset('assets/js/htmx/head.js')" defer></script>
        <script src="@asset('assets/js/htmx/idiomorph.js')" defer></script>
        <script src="@asset('assets/js/htmx/loadingState.js')" defer></script>
    @endif
</head>

<body hx-ext="head-support, loading-states, morph" hx-headers='{"X-CSRF-Token": "{{ csrf_token() }}"}'>
    @if ($currentStep === null || $currentStep === 0)
        {{-- ============================================================ --}}
        {{-- Welcome (step 0) — Full-screen immersive landing              --}}
        {{-- ============================================================ --}}
        <main class="installer-welcome">
            @stack('content')

            @if (isset($sections['content']))
                {!! $sections['content'] !!}
            @endif

            @if (isset($stepView))
                @include($stepView, $stepData ?? [])
            @endif
        </main>
    @else
        {{-- ============================================================ --}}
        {{-- Steps 1-4 — Clean left, contained right                       --}}
        {{-- ============================================================ --}}
        <div class="installer-app">
            {{-- ── Top navbar (outside the contained area) ──────── --}}
            <div class="installer-navbar">
                <div class="installer-navbar__left">
                    <img src="@asset('assets/img/flute_logo.svg')" alt="Flute" class="installer-navbar__logo" />
                </div>
                <div class="installer-navbar__center">
                    <x-step-indicators :steps="$steps" :current-step="$currentStep" />
                </div>
                <div class="installer-navbar__right">
                    <span class="installer-navbar__version">v{{ Flute\Core\App::VERSION }}</span>
                </div>
            </div>

            {{-- ── Main area ─────────────────────────────────────── --}}
            <div class="installer-main">
                {{-- Left: clean open content ──────────────────────── --}}
                <div class="installer-content">
                    <div class="installer-content__inner" hx-swap="morph">
                        @stack('content')

                        @if (isset($sections['content']))
                            {!! $sections['content'] !!}
                        @endif

                        @if (isset($stepView))
                            @include($stepView, $stepData ?? [])
                        @endif
                    </div>
                </div>

                {{-- Right: contained card ──────────────────────────── --}}
                <aside class="installer-aside">
                    <div class="installer-aside__card">
                        {{-- Contextual info --}}
                        <div class="installer-aside__context">
                            <div data-step-context="1" @if($currentStep !== 1) style="display:none" @endif>
                                <div class="aside-step-num">01</div>
                                <div class="aside-title">{{ __('install.lp.check.title') }}</div>
                                <div class="aside-text">{{ __('install.lp.check.text') }}</div>
                            </div>
                            <div data-step-context="2" @if($currentStep !== 2) style="display:none" @endif>
                                <div class="aside-step-num">02</div>
                                <div class="aside-title">{{ __('install.lp.database.title') }}</div>
                                <div class="aside-text">{{ __('install.lp.database.text') }}</div>
                            </div>
                            <div data-step-context="3" @if($currentStep !== 3) style="display:none" @endif>
                                <div class="aside-step-num">03</div>
                                <div class="aside-title">{{ __('install.lp.account.title') }}</div>
                                <div class="aside-text">{{ __('install.lp.account.text') }}</div>
                            </div>
                            <div data-step-context="4" @if($currentStep !== 4) style="display:none" @endif>
                                <div class="aside-step-num">04</div>
                                <div class="aside-title">{{ __('install.lp.languages.title') }}</div>
                                <div class="aside-text">{{ __('install.lp.languages.text') }}</div>
                            </div>
                            <div data-step-context="5" @if($currentStep !== 5) style="display:none" @endif>
                                <div class="aside-step-num">05</div>
                                <div class="aside-title">{{ __('install.lp.modules.title') }}</div>
                                <div class="aside-text">{{ __('install.lp.modules.text') }}</div>
                            </div>
                            <div data-step-context="6" @if($currentStep !== 6) style="display:none" @endif>
                                <div class="aside-step-num">06</div>
                                <div class="aside-title">{{ __('install.lp.launch.title') }}</div>
                                <div class="aside-text">{{ __('install.lp.launch.text') }}</div>
                            </div>
                        </div>

                        {{-- Decorative icons --}}
                        <div class="installer-aside__deco">
                            <div class="deco-icons">
                                <div class="deco-icon {{ $currentStep === 1 ? 'deco-icon--accent' : '' }}">
                                    <x-icon path="ph.regular.cpu" />
                                </div>
                                <div class="deco-icon {{ $currentStep === 2 ? 'deco-icon--accent' : '' }}">
                                    <x-icon path="ph.regular.database" />
                                </div>
                                <div class="deco-icon {{ $currentStep === 3 ? 'deco-icon--accent' : '' }}">
                                    <x-icon path="ph.regular.user-circle" />
                                </div>
                                <div class="deco-icon {{ $currentStep === 4 ? 'deco-icon--accent' : '' }}">
                                    <x-icon path="ph.regular.globe" />
                                </div>
                                <div class="deco-icon {{ $currentStep === 5 ? 'deco-icon--accent' : '' }}">
                                    <x-icon path="ph.regular.puzzle-piece" />
                                </div>
                                <div class="deco-icon {{ $currentStep === 6 ? 'deco-icon--accent' : '' }}">
                                    <x-icon path="ph.regular.rocket-launch" />
                                </div>
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="installer-aside__footer">
                            <span>{{ $currentStep }} / {{ $totalSteps }}</span>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    @endif

    @if (! request()->htmx()->isHtmxRequest())
        <div id="alerts-container">
            @stack('toast-container')

            @if (isset($sections['toast-container']))
                {!! $sections['toast-container'] !!}
            @endif
        </div>
    @endif

    @if (! request()->htmx()->isHtmxRequest())
        <script src="@asset('assets/js/libs/a11y-dialog.js')" defer></script>
        <script src="@asset('assets/js/libs/floating.js')" defer></script>
        <script src="@asset('assets/js/libs/tom-select.js')" defer></script>
        <script src="@asset('jquery')" defer></script>
        <script src="@asset('assets/js/app.js')" defer></script>
        <script src="@asset('assets/js/libs/notyf.js')" defer></script>
        <script src="@asset('assets/js/libs/nprogress.js')" defer></script>

        @at(path('app/Core/Modules/Installer/Resources/assets/js/installer.js'))
        @at(path('app/Core/Modules/Installer/Resources/assets/js/select.js'))

        @stack('scripts')

        @if (isset($sections['scripts']))
            {!! $sections['scripts'] !!}
        @endif
    @endif
</body>

</html>
