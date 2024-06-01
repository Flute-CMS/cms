@mobile_alert
@cookie_alert
@lang_alert

<!DOCTYPE html>
<html lang="{{ app()->getLang() }}" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" type="image/x-icon" href="@asset('favicon.ico')">
    <title>@yield('title', __(page()->title))</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ __(page()->description) }}">
    <meta name="keywords" content="{{ page()->keywords }}">
    <meta name="robots" content="{{ page()->robots }}">
    <meta property="og:title" content="{{ __(page()->og_title) }}">
    <meta property="og:description" content="{{ __(page()->og_description) }}">
    <meta property="og:image" content="{{ page()->og_image }}">

    <link rel='stylesheet' href='@asset('montserrat')' type='text/css'>
    <link rel='stylesheet' href='@asset('sfpro')' type='text/css'>
    <link rel="stylesheet" href="@asset('animate')" type='text/css'>
    <link rel="stylesheet" href="@asset('grid')" type='text/css'>
    <link rel="stylesheet" href="@asset('assets/css/libs/driver.css')">

    <script src="@asset('phosphor')" defer></script>

    @at(tt('assets/styles/main.scss'))

    @if (config('app.bg_image'))
        <style>
            body {
                background-image: url(@asset(config('app.bg_image')));
                background-repeat: no-repeat;
                background-attachment: fixed;
                background-position: center center;
                background-size: cover;
            }
        </style>
    @endif

    @stack('header')
</head>

<body>
    <main>
        @stack('content')
    </main>
    <footer>
        <script src="@asset('jquery')"></script>
        <script src="@asset('assets/js/sweetalert.js')"></script>
        <script src="@asset('assets/js/driver.js')"></script>
        <script src="@asset('assets/js/forms.js')"></script>
        <script src="@asset('assets/js/app.js')" defer></script>


        @at(tt('assets/js/modal.js'))
        @at(tt('assets/js/template.js'))

        @stack('footer')
        <script>
            $(window).on('load', async () => await loadWidgets())
        </script>
    </footer>
    <div class="toast-container">
        @stack('toast-container')
    </div>
</body>

</html>
