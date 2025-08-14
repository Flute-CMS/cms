<!DOCTYPE html>
<html lang="{{ strtolower(app()->getLang()) }}"
    @if (config('app.change_theme', true)) data-theme="{{ cookie()->get('theme', config('app.default_theme', 'dark')) }}"
    @else data-theme="{{ config('app.default_theme', 'dark') }}" @endif>

<head hx-head="append">
    @php
        // --- Title ---
        $_final_title = page()->title;
        if (empty($_final_title) && \Illuminate\Support\Facades\View::hasSection('title')) {
            $_final_title = trim(\Illuminate\Support\Facades\View::yieldContent('title'));
        }
        if (empty($_final_title)) {
            $_final_title = config('app.name');
        }
        $_final_title = __($_final_title);

        // --- Description ---
        $_final_description = page()->description;
        if (empty($_final_description) && \Illuminate\Support\Facades\View::hasSection('description')) {
            $_final_description = trim(\Illuminate\Support\Facades\View::yieldContent('description'));
        }
        if (empty($_final_description)) {
            $_final_description = config('app.description');
        }
        $_final_description = __($_final_description);

        // --- Keywords ---
        $_final_keywords = page()->keywords;
        if (empty($_final_keywords) && \Illuminate\Support\Facades\View::hasSection('keywords')) {
            $_final_keywords = trim(\Illuminate\Support\Facades\View::yieldContent('keywords'));
        }
        if (empty($_final_keywords)) {
            $_final_keywords = config('app.keywords');
        }

        // --- Robots ---
        if (config('app.maintenance_mode')) {
            $_final_robots = 'noindex, nofollow';
        } else {
            $_final_robots = page()->robots;
            if (empty($_final_robots) && \Illuminate\Support\Facades\View::hasSection('robots')) {
                $_final_robots = trim(\Illuminate\Support\Facades\View::yieldContent('robots'));
            }
            if (empty($_final_robots)) {
                $_final_robots = config('app.robots', 'index, follow');
            }
        }

        // --- OG Image ---
        $_final_og_image = page()->og_image;
        if (empty($_final_og_image) && \Illuminate\Support\Facades\View::hasSection('og_image')) {
            $_final_og_image = trim(\Illuminate\Support\Facades\View::yieldContent('og_image'));
        }
        if (empty($_final_og_image)) {
            $_final_og_image = asset('assets/img/social-image.png');
        }

        // --- Twitter Image ---
        $_final_twitter_image = page()->og_image;
        if (empty($_final_twitter_image) && \Illuminate\Support\Facades\View::hasSection('twitter_image')) {
            $_final_twitter_image = trim(\Illuminate\Support\Facades\View::yieldContent('twitter_image'));
        }
        if (empty($_final_twitter_image)) {
            $_final_twitter_image = asset('assets/img/social-image.png');
        }

        // --- HX Detect ---
        $isPartialRequest = request()->htmx()->isHtmxRequest() || request()->htmx()->isBoosted();

        // --- Theme colors ---
        $__colors = app('flute.view.manager')->getColors();
        $lightThemeBg = $__colors['light']['--background'] ?? '#ffffff';
        $darkThemeBg = $__colors['dark']['--background'] ?? '#1c1c1e';
        $currentTheme = config('app.change_theme', true) ? cookie()->get('theme', config('app.default_theme', 'dark')) : config('app.default_theme', 'dark');
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
    <meta name="auth-token" content="{{ md5(user()->isLoggedIn() . '_' . (user()->isLoggedIn() ? user()->id : '')) }}">
    <meta name="google" content="notranslate" />
    <meta name="default-theme" content="{{ config('app.default_theme', 'dark') }}">
    <meta name="change-theme" content="{{ config('app.change_theme', true) ? 'true' : 'false' }}">
    <meta name="description" content="{{ $_final_description }}">
    <meta name="keywords" content="{{ $_final_keywords }}">
    <meta name="robots" content="{{ $_final_robots }}">
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
    <meta property="og:image" content="{{ $_final_og_image }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:locale" content="{{ strtolower(app()->getLang()) }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $_final_title }}">
    <meta name="twitter:description" content="{{ $_final_description }}">
    <meta name="twitter:image" content="{{ $_final_twitter_image }}">

    <meta name="htmx-config"
        content='{
        "defaultFocusScroll": false,
        "scrollIntoViewOnBoost": false,
        "refreshOnHistoryMiss": true,
        "getCacheBusterParam": false,
        "historyCacheSize": 0
    }'>
    <meta name="site_url" content="{{ config('app.url') }}">

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "@yield('schema_type', 'WebPage')",
        "name": "{{ $_final_title }}",
        "description": "{{ $_final_description }}",
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
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="alternate" href="{{ url()->current() }}" hreflang="x-default">

    @foreach (config('lang.available') as $lang)
        <link rel="alternate" href="{{ url() }}?lang={{ $lang }}" hreflang="{{ strtolower($lang) }}">
    @endforeach

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
        <link rel="stylesheet" href="@asset('assets/fonts/manrope/manrope.css')">
        <link rel="stylesheet" href="@asset('animate')" type='text/css'>
        <link rel="stylesheet" href="@asset('grid')" type='text/css'>
        <link rel="stylesheet" href="@asset('assets/css/libs/filepond.min.css')">
        <link rel="stylesheet" href="@asset('assets/css/libs/easymde.min.css')">

        @at(tt('assets/sass/app.scss'))

        <script src="@asset('assets/js/htmx/core.js')"></script>
        <script src="{{ Clickfwd\Yoyo\Services\Configuration::yoyoSrc() }}"></script>

        <script src="@asset('assets/js/htmx/head.js')"></script>
        <script src="@asset('assets/js/htmx/response-targets.js')"></script>
        <script src="@asset('assets/js/htmx/idiomorph.js')"></script>

        @can('admin.pages')
            <script src="@asset('assets/js/libs/gridstack.js')" defer></script>

            <link rel="stylesheet" href="@asset('assets/css/libs/gridstack.min.css')">
        @endcan

        <script src="@asset('assets/js/htmx/loadingState.js')"></script>

        @php echo Clickfwd\Yoyo\Services\Configuration::javascriptInitCode() @endphp
    @endif

    @include('flute::partials.colors')

    <script>
    (function(){
        function updateThemeColor(){
            var m=document.querySelector('meta[name="theme-color"]#theme-color-meta');
            if(!m){m=document.createElement('meta');m.setAttribute('name','theme-color');m.id='theme-color-meta';document.head.appendChild(m)}
            var bg=getComputedStyle(document.documentElement).getPropertyValue('--background').trim()||'{{ $currentThemeBg }}';
            m.setAttribute('content',bg);
            var ms1=document.querySelector('meta[name="msapplication-TileColor"]');
            if(ms1){ms1.setAttribute('content',bg)}
            var ms2=document.querySelector('meta[name="msapplication-navbutton-color"]');
            if(ms2){ms2.setAttribute('content',bg)}
        }
        document.addEventListener('DOMContentLoaded', updateThemeColor);
        var o=new MutationObserver(updateThemeColor);
        o.observe(document.documentElement,{attributes:true,attributeFilter:['data-theme']});
        window.addEventListener('flute:theme-changed', updateThemeColor);
    })();
    </script>
</head>

<body hx-ext="head-support, loading-states, morph, response-targets" hx-history="false"
    hx-headers='{"X-CSRF-Token": "{{ csrf_token() }}"}' itemscope itemtype="https://schema.org/WebPage">

    @if (!$isPartialRequest)
        @can('admin.pages')
            <x-page-edit-nav />
            <x-page-edit-controls />
        @endcan
    @endif

    @includeWhen(!$isPartialRequest, 'flute::layouts.header')

    @if (!$isPartialRequest)
        @can('admin.pages')
            <x-page-edit-widgets />
            <x-page-colors />
            @include('flute::partials.page-edit-onboarding')
        @endcan

        @can('admin.boss')
            @include('flute::partials.admin-onboarding')
        @endcan
    @endif

    <main id="main" class="main-animation" hx-history-elt>
        @includeWhen(!$isPartialRequest, 'flute::partials.loader')

        @stack('before-content')

        @if (isset($sections['before-content']))
            {!! $sections['before-content'] !!}
        @endif

        @if (is_debug() || is_development())
            @include('flute::components.debug-message')
        @endif

        @include('flute::partials.breadcrumb')
        @include('flute::partials.flash')
        @include('flute::partials.widgets')

        @php
            $hasContentWidget = false;
            if (!empty(page()->getBlocks())) {
                foreach (page()->getBlocks() as $block) {
                    if ($block->getWidget() === 'Content') {
                        $hasContentWidget = true;
                        break;
                    }
                }
            }
        @endphp

        @if (empty(page()->getBlocks()) || !$hasContentWidget)
            @stack('content')

            @if (isset($sections['content']))
                {!! $sections['content'] !!}
            @endif
        @endif

        @can('admin.pages')
            @include('flute::partials.no-widgets')
        @endcan
    </main>

    @stack('content-after')

    @if (isset($sections['content-after']))
        {!! $sections['content-after'] !!}
    @endif

    @include('flute::partials.confirmation')

    @if (!$isPartialRequest)
        <div id="alerts-container">
            @stack('toast-container')

            @if (isset($sections['toast-container']))
                {!! $sections['toast-container'] !!}
            @endif
        </div>

        <x-right-sidebar />
        <x-tab-bar />

        @can('admin.pages')
            <x-page-edit />
            @include('flute::partials.page-edit-dialog')
            @include('flute::partials.page-seo-dialog')
        @endcan

        <div id="modals">
            @stack('modals')

            @if (isset($sections['modals']))
                {!! $sections['modals'] !!}
            @endif
        </div>

        <x-user-card />
    @endif

    @includeWhen(!$isPartialRequest, 'flute::components.richtext-icons')

    @includeWhen(!$isPartialRequest, 'flute::layouts.footer')

    @if (!$isPartialRequest)
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
        <script src="@asset('assets/js/libs/easymde.js')" defer></script>

        @at(tt('assets/scripts/libs/simplebar.js'))
        @at(tt('assets/scripts/libs/choices.js'))
        @at(tt('assets/scripts/libs/tinycolor.js'))
        @at(tt('assets/scripts/helpers.js'))
        @at(tt('assets/scripts/bottom-sheet.js'))
        @at(tt('assets/scripts/user-card.js'))
        @at(tt('assets/scripts/tabs.js'))
        @at(tt('assets/scripts/richtext.js'))

        @can('admin.pages')
            @at(tt('assets/scripts/page-edit.js'))
            @at(tt('assets/scripts/page-color.js'))
        @endcan

        @can('admin.boss')
            @if (!config('tips_complete.admin_onboarding.completed'))
                @at(tt('assets/scripts/admin-onboarding.js'))
            @endif
        @endcan

        @at(tt('assets/scripts/app.js'))

        @include('flute::partials.toasts')

        @stack('scripts')

        @if (isset($sections['scripts']))
            {!! $sections['scripts'] !!}
        @endif
    @endif
</body>

</html>
