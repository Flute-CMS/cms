@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.main')]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/main.scss')
@endpush

@push('content')
    <div class="admin-header">
        <h2>@t('admin.dashboard_page.header')</h2>
        <p>@t('admin.dashboard_page.description')</p>
    </div>

    @include('Core.Admin.Http.Views.components.charts')
    @stack('admin-main::charts')
@endpush

@push('footer')
    <script src="{{ chart()->cdn() }}"></script>
@endpush
