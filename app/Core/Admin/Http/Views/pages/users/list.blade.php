
@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.users.list')]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/users.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <h2>@t('admin.users.header')</h2>
            <p>@t('admin.users.description')</p>
        </div>
    </div>

    {!! $users !!}
@endpush

@push('footer')
    @at('Core/Admin/Http/Views/assets/js/pages/users/list.js')
@endpush
