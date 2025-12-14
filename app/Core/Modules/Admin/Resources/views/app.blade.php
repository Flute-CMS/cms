<!DOCTYPE html>
<html lang="{{ app()->getLang() }}" data-theme="{{ cookie()->get('theme', 'dark') }}"
    data-color-scheme="{{ cookie()->get('color-scheme', 'default') }}">

<head hx-head="append">
    <title>
        @yield('title', __(page()->title ?? config('app.name')))
        @hasSection('title')
            - {{ config('app.name') }}
        @endif
    </title>

    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no">
    <meta name="view-transition" content="same-origin">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="auth" id="auth" content="{{ user()->isLoggedIn() }}">
    <meta name="application-name" content="{{ config('app.name') }}">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="color-scheme" content="dark light">
    <meta name="supported-color-schemes" content="dark light">

    @stack('head')

    @if (isset($sections['head']))
        {!! $sections['head'] !!}
    @endif

    <meta name="description" content="{{ __(page()->description ?? '') }}">
    <meta name="keywords" content="{{ page()->keywords ?? '' }}">
    <meta name="robots" content="{{ page()->robots ?? '' }}">
    <meta property="og:title" content="{{ __(page()->og_title ?? '') }}">
    <meta property="og:description" content="{{ __(page()->og_description ?? '') }}">
    <meta property="og:image" content="{{ page()->og_image }}">

    <meta name="htmx-config"
        content='{
        "defaultFocusScroll": false,
        "scrollIntoViewOnBoost": false,
        "refreshOnHistoryMiss": true,
        "historyCacheSize": 0
    }'>
    <meta name="site_url" content="{{ config('app.url') }}">

    <link rel="icon" type="image/x-icon" href="@asset('favicon.ico')">

    @stack('styles')

    @if (isset($sections['styles']))
        {!! $sections['styles'] !!}
    @endif

    {{-- For support head merge --}}
    @if (request()->htmx()->isHtmxRequest())
        @stack('scripts')

        @if (isset($sections['scripts']))
            {!! $sections['scripts'] !!}
        @endif
    @endif

    @if (!request()->htmx()->isHtmxRequest())
        <link rel="stylesheet" href="@asset('assets/fonts/manrope/manrope.css')">
        <link rel="stylesheet" href="@asset('animate')" type='text/css'>
        <link rel="stylesheet" href="@asset('grid')" type='text/css'>
        <link rel="stylesheet" href="@asset('assets/css/libs/filepond.min.css')">
        <link rel="stylesheet" href="@asset('assets/css/libs/easymde.min.css')">

        {{-- SCSS assets --}}
        @at('Core/Modules/Admin/Resources/assets/sass/admin.scss')

        <script src="@asset('assets/js/htmx/core.js')"></script>
        <script src="{{ Clickfwd\Yoyo\Services\Configuration::yoyoSrc() }}"></script>

        <script src="@asset('assets/js/htmx/head.js')"></script>
        <script src="@asset('assets/js/htmx/idiomorph.js')"></script>

        <script src="@asset('assets/js/htmx/loadingState.js')"></script>

        @php echo Clickfwd\Yoyo\Services\Configuration::javascriptInitCode() @endphp
    @endif

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
                '#1c1c1e';
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

<body hx-ext="head-support, loading-states, morph" @class([
    'sidebar-collapsed' =>
        cookie()->get('admin-sidebar-collapsed', 'false') === 'true' &&
        !user()->device()->isMobile(),
])>
    @includeWhen(!request()->htmx()->isHtmxRequest(), 'admin::layouts.sidebar')

    @if (!request()->htmx()->isHtmxRequest())
        <main>
    @endif
    @includeWhen(!request()->htmx()->isHtmxRequest(), 'admin::layouts.header')

    @includeWhen(!request()->htmx()->isHtmxRequest(), 'admin::partials.loader')

    @yield('before-content')

    @if (isset($sections['before-content']))
        {!! $sections['before-content'] !!}
    @endif

    @include('admin::partials.flash')

    @if (is_development() && !request()->htmx()->isHtmxRequest())
        <div class="container mt-2">
            <x-alert type="warning" onlyBorders withClose="false">
                {{ __('def.debug_message') }} (development mode)
            </x-alert>
        </div>
    @endif

    <div class="main-animation @if (cookie()->get('container-width', 'normal') === 'wide') container-wide @endif container" id="main"
        hx-history-elt>
        @stack('content')

        @if (isset($sections['content']))
            {!! $sections['content'] !!}
        @endif

        <footer class="d-flex flex-center flex-column text-muted mb-4 mt-4" hx-swap="none">
            <p>
                © {{ date('Y') }} Flute. Version: <strong>{{ app()->getVersion() }}</strong>.
            </p>
            <p>Developed by <a class="hover-accent" href="https://github.com/FlamesONE" target="_blank">Flames</a> with
                <span class="secret-confetti cursor-pointer" id="secret-confetti">❤️</span>.
            </p>
            @php
                $startTime = microtime(true);
                $executionTime = round($startTime - FLUTE_START, 2);
                $times = [];

                foreach (app()->getBootTimes() as $key => $value) {
                    $key = explode('\\', $key);
                    $key = end($key);
                    $key = str_replace('ServiceProvider', '', $key);
                    $times[] = "[{$key}] {$value}ms";
                }
            @endphp
            <small class="text-muted mt-3">Booted in <strong
                    data-tooltip="{!! implode("\n", $times) !!}">{{ $executionTime }}</strong> seconds

                @if ($executionTime > 1)
                    <x-popover content="{!! __('admin.performance_info') !!}" />
                @endif
            </small>
        </footer>
    </div>

    @includeWhen(!request()->htmx()->isHtmxRequest(), 'admin::layouts.footer')
    @if (!request()->htmx()->isHtmxRequest())
        </main>
    @endif

    @stack('content-after')

    @if (isset($sections['content-after']))
        {!! $sections['content-after'] !!}
    @endif

    @if (!request()->htmx()->isHtmxRequest())
        <div id="alerts-container">
            @stack('toast-container')

            @if (isset($sections['toast-container']))
                {!! $sections['toast-container'] !!}
            @endif
        </div>

        <x-right-sidebar />

        @include('admin::partials.confirmation')
        @include('admin::partials.search')
        @include('admin::partials.scrollup')
        @include('admin::partials.customization')
    @endif

    @if (!request()->htmx()->isHtmxRequest())
        <footer>
            @php
                if (is_debug()) {
                    Tracy\Debugger::renderLoader();
                }
            @endphp

            @stack('footer')

            @if (isset($sections['footer']))
                {!! $sections['footer'] !!}
            @endif

            @include('admin::components.richtext-icons')
        </footer>

        <script src="@asset('assets/js/libs/a11y-dialog.js')" defer></script>
        <script src="@asset('assets/js/libs/floating.js')" defer></script>
        <script src="@asset('jquery')" defer></script>
        <script src="@asset('assets/js/app.js')" defer></script>
        <script src="@asset('assets/js/libs/filepond-image-preview.js')" defer></script>
        <script src="@asset('assets/js/libs/filepond-validate.js')" defer></script>
        <script src="@asset('assets/js/libs/filepond.js')" defer></script>
        <script src="@asset('assets/js/libs/notyf.js')" defer></script>
        <script src="@asset('assets/js/libs/nprogress.js')" defer></script>
        <script src="@asset('assets/js/libs/sortable.js')" defer></script>
        <script src="@asset('assets/js/libs/confetti.js')" defer></script>
        <script src="@asset('assets/js/libs/tom-select.js')" defer></script>
        <script src="@asset('assets/js/libs/easymde.js')" defer></script>
        <!-- <script src="@asset('assets/js/libs/flatpickr.js')" defer></script> -->
        <script src="@asset('assets/js/libs/pickr.js')" defer></script>

        @at('Core/Modules/Admin/Resources/assets/js/helpers.js')
        @at('Core/Modules/Admin/Resources/assets/js/modals.js')
        @at('Core/Modules/Admin/Resources/assets/js/tabs.js')
        @at('Core/Modules/Admin/Resources/assets/js/popover.js')
        @at('Core/Modules/Admin/Resources/assets/js/columns.js')
        @at('Core/Modules/Admin/Resources/assets/js/selection.js')
        @at('Core/Modules/Admin/Resources/assets/js/script.js')
        @at('Core/Modules/Admin/Resources/assets/js/sidebar.js')
        @at('Core/Modules/Admin/Resources/assets/js/sortable.js')
        @at('Core/Modules/Admin/Resources/assets/js/search.js')
        @at('Core/Modules/Admin/Resources/assets/js/secret.js')
        @at('Core/Modules/Admin/Resources/assets/js/scrollup.js')
        @at('Core/Modules/Admin/Resources/assets/js/select.js')
        @at('Core/Modules/Admin/Resources/assets/js/table-search.js')
        @at('Core/Modules/Admin/Resources/assets/js/richtext.js')
        @at('Core/Modules/Admin/Resources/assets/js/customization.js')
        @at('Core/Modules/Admin/Resources/assets/js/confirm.js')
        @at('Core/Modules/Admin/Resources/assets/js/input.js')
        @at('Core/Modules/Admin/Resources/assets/js/dirty.js')

        @include('admin::partials.toasts')

        @stack('scripts')

        @if (isset($sections['scripts']))
            {!! $sections['scripts'] !!}
        @endif
    @endif
</body>

</html>
