@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.users_blocks.title')]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/users_blocks.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <h2>@t('admin.users_blocks.title')</h2>
            <p>@t('admin.users_blocks.description')</p>
        </div>
    </div>

    {!! $table !!}
@endpush

@push('footer')
    @at('Core/Admin/Http/Views/assets/js/pages/users_blocks/list.js')
@endpush
