<!DOCTYPE html>
<html lang="{{ config('installer.params.lang', 'ru') }}" data-theme="dark"
    data-step="{{ app('installer.view')->stepCurrent() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title')</title>
    <link rel='stylesheet' href='@asset('montserrat')' type='text/css'>
    <link rel='stylesheet' href='@asset('sfpro')' type='text/css'>
    <link rel="stylesheet" href="@asset('animate')" type='text/css'>
    <link rel="stylesheet" href="@asset('grid')" type='text/css'>

    <script src="@asset('phosphor')"></script>

    @stack('header')
</head>

<body>
    <div class="errors_background" style="display: none"></div>
    <div id="errors_container" style="display: none">
    </div>
    <main>
        <div id="content">
            @stack('content')
        </div>

        <div class="logo-container">
            <h1 class="text-logo">Flute engine</h1>
            <img class="animate__animated logo-image" src="@at('assets/img/flute_logo.svg')" alt="Logo">
        </div>

        @include('Core/Http/Views/Installer/steps/steps.component.blade.php', [
            'current' => app('installer.view')->stepCurrent(),
            'all' => app('installer.view')->stepAll(),
        ])
    </main>
    <footer>
        <script src="@asset('jquery')"></script>
        <script src="@asset('/assets/js/forms.js')"></script>
        <script src="@asset('appjs')"></script>
        @at('Core/Http/Views/Installer/assets/js/install.js')
        @stack('footer')
    </footer>
</body>

</html>
