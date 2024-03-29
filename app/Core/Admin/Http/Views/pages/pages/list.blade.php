@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.pages.title')]),
])

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <h2>@t('admin.pages.header')</h2>
            <p>@t('admin.pages.description')</p>
        </div>
        <div>
            <a href="{{ url('admin/pages/add') }}" class="btn size-s outline">
                @t('admin.pages.add')
            </a>
        </div>
    </div>

    {!! $table !!}
@endpush