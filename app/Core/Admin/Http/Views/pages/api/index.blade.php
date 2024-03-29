@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.api.header')]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/api.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <h2>@t('admin.api.header')</h2>
            <p>@t('admin.api.description')</p>
        </div>
        <div>
            <a href="{{ url('admin/api/add') }}" class="btn size-s outline">
                @t('admin.api.add')
            </a>
        </div>
    </div>

    {!! $table !!}
@endpush