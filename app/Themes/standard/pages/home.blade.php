@extends(tt('layout.blade.php'))

@section('title')
    {{ !empty(page()->title) ? page()->title : __('def.home') }}
@endsection

@push('content')
    @navbar
    <div class="container">
        @navigation
        @breadcrumb
        @flash
        @editor

        @stack('container')
    </div>
@endpush

@if (tip_active('editor'))
    @push('footer')
        <script>
            const IS_EDITING = {{ (int) page()->isEditMode() }};
        </script>
        @at(tt('assets/js/pages/home.js'))
    @endpush
@endif

@footer
