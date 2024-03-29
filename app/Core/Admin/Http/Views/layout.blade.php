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

            <div class="content">
                @admin_navbar
                @breadcrumb

                @stack('content')
            </div>
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

        @stack('footer')
    </footer>
</body>

</html>
