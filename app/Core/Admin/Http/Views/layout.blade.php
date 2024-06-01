@if (request()->input('loadByTab'))

    <div data-page-title>@yield('title', $title ?? '')</div>

    <head>
        @stack('header')
    </head>

    @stack('content')

    @stack('footer')
@else
    <!DOCTYPE html>
    <html lang="{{ app()->getLang() }}" data-theme="dark">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', $title ?? '')</title>
        <link rel='stylesheet' href='@asset('montserrat')' type='text/css'>
        <link rel='stylesheet' href='@asset('sfpro')' type='text/css'>
        <link rel="stylesheet" href="@asset('animate')" type='text/css'>
        <link rel="stylesheet" href="@asset('grid')" type='text/css'>
        <link rel="stylesheet" href="@asset('assets/css/libs/driver.css')">

        <script src="@asset('phosphor')"></script>

        @at('Core/Admin/Http/Views/assets/styles/admin_main.scss')

        @stack('header')
    </head>

    <body>
        <main>
            <div class="admin-container">
                @admin_sidebar

                <section>
                    @admin_navbar
                    <div class="content">
                        <div id="start_page" hidden>
                            @admin_start_page
                        </div>
                        <div id="loading">
                            <div class="loading-content">
                                <span class="loader"></span>
                                <h1>@t('admin.is_loading')</h1>
                                <p>@t('admin.is_loading_desc')</p>
                            </div>
                        </div>
                        <div id="error_page" hidden>
                            <div class="error_page-content">
                                <i class="ph ph-smiley-sad"></i>
                                <h1>@t('admin.error_page')</h1>
                                <a id="closeErrorBlock">@t('def.close')</a>
                            </div>
                        </div>

                        <div id="contents_page">
                            {{-- @stack('content') --}}
                        </div>

                        @breadcrumb
                    </div>
                </section>
            </div>
        </main>
        <footer>
            <script src="@asset('jquery')"></script>
            <script src="@asset('/assets/js/forms.js')"></script>
            <script src="@asset('assets/js/driver.js')"></script>
            <script src="@asset('appjs')"></script>
            @at('Core/Admin/Http/Views/assets/js/layout.js')

            @at(tt('assets/js/modal.js'))

            @if (tip_active('admin_stats'))
                @at('Core/Admin/Http/Views/assets/js/tips/main.js')
            @endif

            {{-- @stack('footer') --}}
            @at('Core/Admin/Http/Views/assets/js/components/confirm.js')
            @at('Core/Admin/Http/Views/assets/js/components/sidebar.js')
            @at('Core/Admin/Http/Views/assets/js/components/header.js')
            @at('Core/Admin/Http/Views/assets/js/components/tabs.js')
            <script src="https://unpkg.com/draggabilly@2.2.0/dist/draggabilly.pkgd.min.js"></script>
        </footer>
    </body>

    </html>

@endif
