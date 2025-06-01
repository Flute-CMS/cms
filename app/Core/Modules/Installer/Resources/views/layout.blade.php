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
        <link rel="stylesheet" href="@asset('assets/css/libs/filepond.min.css')">

        @at(path('app/Core/Modules/Installer/Resources/assets/sass/installer.scss'))

        <script src="@asset('assets/js/htmx/core.js')"></script>
        <script src="{{ Clickfwd\Yoyo\Services\Configuration::yoyoSrc() }}"></script>

        <script src="@asset('assets/js/htmx/head.js')"></script>
        <script src="@asset('assets/js/htmx/idiomorph.js')"></script>

        <script src="@asset('assets/js/htmx/loadingState.js')"></script>

        @php echo Clickfwd\Yoyo\Services\Configuration::javascriptInitCode() @endphp
    @endif
</head>

<body hx-ext="head-support, loading-states, morph" hx-headers='{"X-CSRF-Token": "{{ csrf_token() }}"}'>
    <main @class([
        'installer',
        'isStep' => $step > 0
    ])>
        <div class="installer__content" hx-swap="morph">
            <header class="installer__header">
                <img src="@asset('assets/img/flute_logo.svg')" alt="Flute logo" class="logo" />
            </header>

            <div class="installer__container">
                @stack('content')

                @if (isset($sections['content']))
                    {!! $sections['content'] !!}
                @endif

                @if (isset($component))
                    @yoyo($component, get_defined_vars())
                @endif

                <div hx-get="{{ route('installer.step', ['id' => $currentStep + 1]) }}" hx-swap="morph"
                    hx-push-url="true" hx-trigger="step-changed" variant="primary" id="step-changed">
                </div>
            </div>
        </div>

        @if ($step > 0)
            <footer class="installer__footer">
                <x-step-indicators :steps="$steps" :current-step="$currentStep" />
            </footer>
        @endif
    </main>

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
        <script src="@asset('jquery')"></script>
        <script src="@asset('assets/js/app.js')" defer></script>
        <script src="@asset('assets/js/libs/notyf.js')" defer></script>
        <script src="@asset('assets/js/libs/nprogress.js')" defer></script>

        @at(path('app/Core/Modules/Installer/Resources/assets/js/installer.js'))

        @stack('scripts')

        @if (isset($sections['scripts']))
            {!! $sections['scripts'] !!}
        @endif
    @endif
</body>

</html>