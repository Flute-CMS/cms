@extends(tt('layout.blade.php'))

@push('content')
    @navbar
    <div class="container">
        @navigation
        @breadcrumb
        @flash

        <h1>{{ page()->title }} </h1>
        @editor

        @stack('container')
    </div>
@endpush

@footer
