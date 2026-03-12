<!DOCTYPE html>
@php
    $_currentThemeMode = config('app.change_theme', true)
        ? cookie()->get('theme', config('app.default_theme', 'dark'))
        : config('app.default_theme', 'dark');
    $_themeColors = app('flute.view.manager')->getColors($_currentThemeMode);
    $_navStyle = $_themeColors['--nav-style'] ?? 'default';
    $_sidebarStyle = $_themeColors['--sidebar-style'] ?? 'default';
    $_sidebarMode = $_themeColors['--sidebar-mode'] ?? 'full';
    $_sidebarPosition = $_themeColors['--sidebar-position'] ?? 'top';
    $_sidebarCollapsed = cookie()->get('sidebar_collapsed', 'false');
    $_sidebarContained = $_themeColors['--sidebar-contained'] ?? 'false';
    $_designPreset = $_themeColors['--design-preset'] ?? 'default';
@endphp
<html lang="{{ strtolower(app()->getLang()) }}" data-theme="{{ $_currentThemeMode }}" data-nav-style="{{ $_navStyle }}"
    data-sidebar-style="{{ $_sidebarStyle }}" data-sidebar-mode="{{ $_sidebarMode }}"
    data-sidebar-position="{{ $_sidebarPosition }}" data-sidebar-collapsed="{{ $_sidebarCollapsed }}"
    data-sidebar-contained="{{ $_sidebarContained }}" data-design-preset="{{ $_designPreset }}">

<head hx-head="append">
    @php
        // --- Title ---
        $_final_title = '';
        if (\Illuminate\Support\Facades\View::hasSection('title')) {
            $_final_title = trim(\Illuminate\Support\Facades\View::yieldContent('title'));
        }
        if (empty($_final_title)) {
            $_final_title = config('app.name');
        }
        $_final_title = __($_final_title);

        // --- Description ---
        $_final_description = config('app.description');
        $_final_description = __($_final_description);

        // --- HX Detect ---
        $isPartialRequest = request()->htmx()->isHtmxRequest() || request()->htmx()->isBoosted();

        // --- Theme colors ---
        $__colors = app('flute.view.manager')->getColors();
        $lightThemeBg = $__colors['light']['--background'] ?? '#ffffff';
        $darkThemeBg = $__colors['dark']['--background'] ?? '#1c1c1e';
        $currentTheme = config('app.change_theme', true)
            ? cookie()->get('theme', config('app.default_theme', 'dark'))
            : config('app.default_theme', 'dark');
        $currentThemeBg = $currentTheme === 'light' ? $lightThemeBg : $darkThemeBg;
    @endphp
    <title>
        {{ $_final_title }}
    </title>

    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport"
        content="minimum-scale=1, initial-scale=1, width=device-width, shrink-to-fit=no, user-scalable=no, viewport-fit=cover">
    <meta name="view-transition" content="same-origin">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="auth" id="auth" content="{{ user()->isLoggedIn() ? 'true' : 'false' }}">
    <meta name="state-token"
        content="{{ md5(user()->isLoggedIn() . '_' . (user()->isLoggedIn() ? user()->id : '')) }}">
    <meta name="google" content="notranslate" />
    <meta name="default-theme" content="{{ config('app.default_theme', 'dark') }}">
    <meta name="change-theme" content="{{ config('app.change_theme', true) ? 'true' : 'false' }}">
    <meta name="description" content="{{ $_final_description }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="author" content="Flames">
    <meta name="application-name" content="{{ config('app.name') }}">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="color-scheme" content="dark light">
    <meta name="supported-color-schemes" content="dark light">
    <meta name="msapplication-TileColor" content="{{ $currentThemeBg }}">
    <meta name="msapplication-navbutton-color" content="{{ $currentThemeBg }}">
    <meta name="theme-color" id="theme-color-meta" content="{{ $currentThemeBg }}">
    <meta name="theme-color" content="{{ $lightThemeBg }}" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="{{ $darkThemeBg }}" media="(prefers-color-scheme: dark)">

    <meta property="og:title" content="{{ $_final_title }}">
    <meta property="og:description" content="{{ $_final_description }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:locale" content="{{ strtolower(app()->getLang()) }}">

    <meta name="htmx-config"
        content='{
        "defaultFocusScroll": false,
        "scrollIntoViewOnBoost": false,
        "refreshOnHistoryMiss": true,
        "getCacheBusterParam": false,
        "historyCacheSize": 0
    }'>
    <meta name="site_url" content="{{ config('app.url') }}">

    @yield('meta')
    @stack('head')

    @if (isset($sections['head']))
        {!! $sections['head'] !!}
    @endif

    <link rel="icon" type="image/x-icon" href="@asset('favicon.ico')">
    <link rel="canonical" href="{{ url()->current() }}">

    @include('flute::partials.background')

    @stack('styles')

    @if (isset($sections['styles']))
        {!! $sections['styles'] !!}
    @endif

    {{-- For support head merge (hx-only & hx-boost) --}}
    @if ($isPartialRequest)
        @stack('scripts')

        @if (isset($sections['scripts']))
            {!! $sections['scripts'] !!}
        @endif
    @endif

    @if (!$isPartialRequest)
        <link rel="preload" href="@asset('assets/fonts/manrope/Manrope-Regular.woff2')" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="@asset('assets/fonts/manrope/Manrope-Medium.woff2')" as="font" type="font/woff2" crossorigin>
        <link rel="stylesheet" href="@asset('assets/fonts/manrope/manrope.css')">
        <link rel="preload" href="@asset('animate')" as="style"
            onload="this.onload=null;this.rel='stylesheet'">
        <noscript>
            <link rel="stylesheet" href="@asset('animate')" type='text/css'>
        </noscript>
        <link rel="stylesheet" href="@asset('grid')" type='text/css'>

        @at(tt('assets/sass/app.scss'))

        <script src="@asset('assets/js/htmx/core.js')"></script>
        <script src="{{ Clickfwd\Yoyo\Services\Configuration::yoyoSrc() }}"></script>

        <script src="@asset('assets/js/htmx/head.js')"></script>
        <script src="@asset('assets/js/htmx/response-targets.js')"></script>
        <script src="@asset('assets/js/htmx/idiomorph.js')"></script>

        <script src="@asset('assets/js/htmx/loadingState.js')"></script>

        @php echo Clickfwd\Yoyo\Services\Configuration::javascriptInitCode() @endphp
    @endif

    @include('flute::partials.colors')

    <script>
        (function() {
            function updateThemeColor() {
                var m = document.querySelector('meta[name="theme-color"]#theme-color-meta');
                if (!m) {
                    m = document.createElement('meta');
                    m.setAttribute('name', 'theme-color');
                    m.id = 'theme-color-meta';
                    document.head.appendChild(m)
                }
                var bg = getComputedStyle(document.documentElement).getPropertyValue('--background').trim() ||
                    '{{ $currentThemeBg }}';
                m.setAttribute('content', bg);
                var ms1 = document.querySelector('meta[name="msapplication-TileColor"]');
                if (ms1) {
                    ms1.setAttribute('content', bg)
                }
                var ms2 = document.querySelector('meta[name="msapplication-navbutton-color"]');
                if (ms2) {
                    ms2.setAttribute('content', bg)
                }
            }
            document.addEventListener('DOMContentLoaded', updateThemeColor);
            var o = new MutationObserver(updateThemeColor);
            o.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['data-theme']
            });
            window.addEventListener('flute:theme-changed', updateThemeColor);
        })();
    </script>
</head>

<body hx-ext="head-support, loading-states, morph, response-targets" hx-history="false"
    hx-headers='{"X-CSRF-Token": "{{ csrf_token() }}"}' itemscope itemtype="https://schema.org/WebPage">

    @if (!$isPartialRequest)
        {{-- Always render sidebar-nav, visibility controlled by CSS based on data-nav-style --}}
        <x-sidebar-nav />
        <div class="content-frame" id="content-frame"></div>
        <div class="frame-blur" id="frame-blur"></div>
        <button type="button" class="sidebar-contained-toggle" id="sidebar-contained-toggle"
            aria-label="{{ __('def.toggle_sidebar') }}">
            <x-icon path="ph.regular.sidebar-simple" />
        </button>
    @endif

    @includeWhen(!$isPartialRequest, 'flute::layouts.header')

    <main id="main" class="main-animation" hx-history-elt>
        @includeWhen(!$isPartialRequest, 'flute::partials.loader')

        @include('flute::partials.flash')

        @stack('content')

        @if (isset($sections['content']))
            {!! $sections['content'] !!}
        @endif
    </main>

    @stack('content-after')

    @if (isset($sections['content-after']))
        {!! $sections['content-after'] !!}
    @endif

    @includeIf('flute::partials.confirmation')

    @if (!$isPartialRequest)
        <div id="alerts-container">
            @stack('toast-container')

            @if (isset($sections['toast-container']))
                {!! $sections['toast-container'] !!}
            @endif
        </div>

        @includeIf('flute::components.right-sidebar')
        @includeIf('flute::components.tab-bar')

        <div id="modals">
            @include('flute::partials.default-modals')

            @stack('modals')

            @if (isset($sections['modals']))
                {!! $sections['modals'] !!}
            @endif
        </div>

        @includeIf('flute::components.user-card')
    @endif

    @includeWhen(!$isPartialRequest, 'flute::layouts.footer')

    @if (!$isPartialRequest)
        <div class="footer-scripts">
            @php
                if (is_debug()) {
                    Tracy\Debugger::renderLoader();
                }
            @endphp

            @stack('footer')

            @if (isset($sections['footer']))
                {!! $sections['footer'] !!}
            @endif
        </div>

        <script src="@asset('assets/js/libs/a11y-dialog.js')" defer></script>
        <script src="@asset('assets/js/libs/floating.js')" defer></script>
        <script src="@asset('jquery')" defer></script>
        <script src="@asset('assets/js/app.js')" defer></script>
        <script src="@asset('assets/js/libs/notyf.js')" defer></script>
        <script src="@asset('assets/js/libs/nprogress.js')" defer></script>

        <script src="@asset('assets/js/libs/tom-select.js')" defer></script>

        @at(tt('assets/scripts/libs/simplebar.js'))
        @at(tt('assets/scripts/libs/tinycolor.js'))
        @at(tt('assets/scripts/helpers.js'))
        @at(tt('assets/scripts/bottom-sheet.js'))
        @at(tt('assets/scripts/user-card.js'))
        @at(tt('assets/scripts/tabs.js'))
        @at(tt('assets/scripts/tom-select.js'))

        {{-- Always load sidebar-nav script, it handles visibility check internally --}}
        @at(tt(path: 'assets/scripts/sidebar-nav.js'))

        @at(tt('assets/scripts/app.js'))

        @include('flute::partials.toasts')

        @stack('scripts')

        @if (isset($sections['scripts']))
            {!! $sections['scripts'] !!}
        @endif
    @endif
</body>

</html>
