<!DOCTYPE html>
<html lang="{{ strtolower(app()->getLang()) }}"
    @if (config('app.change_theme', true)) data-theme="{{ cookie()->get('theme', config('app.default_theme', 'dark')) }}" @else data-theme="{{ config('app.default_theme', 'dark') }}" @endif>

<head hx-head="append">
    <title>
        @yield('title', __(empty(page()->title) ? config('app.name') : page()->title))

        @hasSection('title')
            - {{ config('app.name') }}
        @endif

        @if (page()->title)
            - {{ config('app.name') }}
        @endif
    </title>

    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport"
        content="minimum-scale=1, initial-scale=1, width=device-width, shrink-to-fit=no, user-scalable=no, viewport-fit=cover">
    <meta name="view-transition" content="same-origin">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="auth" id="auth" content="{{ user()->isLoggedIn() }}">
    <meta name="google" content="notranslate" />

    <meta name="description" content="@yield('description', __(empty(page()->description) ? config('app.description') : page()->description))">
    <meta name="keywords" content="@yield('keywords', page()->keywords ?? config('app.keywords'))">
    <meta name="robots"
        content="@if (config('app.maintenance_mode')) noindex, nofollow @else @yield('robots', page()->robots ?? config('app.robots', 'index, follow')) @endif">
    <meta name="author" content="Flames">

    <meta property="og:title" content="@yield('title', __(empty(page()->title) ? config('app.name') : page()->title))">
    <meta property="og:description" content="@yield('description', __(empty(page()->description) ? config('app.description') : page()->description))">
    <meta property="og:image" content="@yield('og_image', page()->og_image ?? asset('assets/img/social-image.png'))">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:locale" content="{{ strtolower(app()->getLang()) }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', __(empty(page()->title) ? config('app.name') : page()->title))">
    <meta name="twitter:description" content="@yield('description', __(empty(page()->description) ? config('app.description') : page()->description))">
    <meta name="twitter:image" content="@yield('twitter_image', page()->og_image ?? asset('assets/img/social-image.png'))">

    <meta name="htmx-config"
        content='{
        "defaultFocusScroll": false,
        "scrollIntoViewOnBoost": false,
        "refreshOnHistoryMiss": true,
        "historyCacheSize": 0
    }'>
    <meta name="site_url" content="{{ config('app.url') }}">

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "@yield('schema_type', 'WebPage')",
        "name": "@yield('title', __(page()->title ?? config('app.name')))",
        "description": "@yield('description', __(page()->description ?? config('app.description')))",
        "url": "{{ url()->current() }}",
        "inLanguage": "{{ strtolower(app()->getLang()) }}",
        "isPartOf": {
            "@type": "WebSite",
            "name": "{{ config('app.name') }}",
            "url": "{{ config('app.url') }}"
        }
    }
    </script>

    @yield('meta')
    @stack('head')

    @if (isset($sections['head']))
        {!! $sections['head'] !!}
    @endif

    <link rel="icon" type="image/x-icon" href="@asset('favicon.ico')">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <link rel="alternate" href="{{ url()->current() }}" hreflang="x-default">

    @foreach (config('lang.available') as $lang)
        <link rel="alternate" href="{{ url()->addParams(['lang' => $lang]) }}" hreflang="{{ strtolower($lang) }}">
    @endforeach

    @include('flute::partials.background')

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

    @if (!request()->htmx()->isHtmxRequest())
        <link rel="stylesheet" href="@asset('assets/fonts/manrope/manrope.css')">
        <link rel="stylesheet" href="@asset('animate')" type='text/css'>
        <link rel="stylesheet" href="@asset('grid')" type='text/css'>

        @at(tt('assets/sass/app.scss'))

        <script src="@asset('assets/js/htmx/core.js')"></script>
        <script src="{{ Clickfwd\Yoyo\Services\Configuration::yoyoSrc() }}"></script>

        <script src="@asset('assets/js/htmx/head.js')"></script>
        <script src="@asset('assets/js/htmx/idiomorph.js')"></script>

        <script src="@asset('assets/js/htmx/loadingState.js')"></script>

        @php echo Clickfwd\Yoyo\Services\Configuration::javascriptInitCode() @endphp
    @endif

    @include('flute::partials.colors')
</head>

<body hx-ext="head-support, loading-states, morph" hx-headers='{"X-CSRF-Token": "{{ csrf_token() }}"}' itemscope
    itemtype="https://schema.org/WebPage">
    @can('admin.pages')
        <x-page-edit-nav />
    @endcan

    @includeWhen(!request()->htmx()->isHtmxRequest(), 'flute::layouts.header')

    <main id="main" class="main-animation">
        @includeWhen(!request()->htmx()->isHtmxRequest(), 'flute::partials.loader')

        @stack('before-content')

        @if (isset($sections['before-content']))
            {!! $sections['before-content'] !!}
        @endif

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

    @if (!request()->htmx()->isHtmxRequest())
        <x-right-sidebar />

        <div id="alerts-container">
            @stack('toast-container')

            @if (isset($sections['toast-container']))
                {!! $sections['toast-container'] !!}
            @endif
        </div>
    @endif

    <div id="modals">
        @stack('modals')

        @if (isset($sections['modals']))
            {!! $sections['modals'] !!}
        @endif
    </div>

    @includeWhen(!request()->htmx()->isHtmxRequest(), 'flute::layouts.footer')

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
        </footer>

        <script src="@asset('assets/js/libs/a11y-dialog.js')" defer></script>
        <script src="@asset('assets/js/libs/floating.js')" defer></script>
        <script src="@asset('jquery')"></script>
        <script src="@asset('assets/js/app.js')" defer></script>
        <script src="@asset('assets/js/libs/filepond-image-preview.js')" defer></script>
        <script src="@asset('assets/js/libs/filepond-validate.js')" defer></script>
        <script src="@asset('assets/js/libs/filepond.js')" defer></script>
        <script src="@asset('assets/js/libs/notyf.js')" defer></script>
        <script src="@asset('assets/js/libs/nprogress.js')" defer></script>

        @at(tt('assets/scripts/libs/simplebar.js'))
        @at(tt('assets/scripts/libs/choices.js'))
        @at(tt('assets/scripts/libs/tinycolor.js'))

        @at(tt('assets/scripts/helpers.js'))
        @at(tt('assets/scripts/bottom-sheet.js'))
        @at(tt('assets/scripts/user-card.js'))
        @at(tt('assets/scripts/tabs.js'))

        @at(tt('assets/scripts/app.js'))

        @stack('scripts')

        @if (isset($sections['scripts']))
            {!! $sections['scripts'] !!}
        @endif
    @endif
</body>

</html>
