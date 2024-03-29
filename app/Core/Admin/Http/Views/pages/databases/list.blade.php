
@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.databases.title')]),
])

@push('header')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <h2>@t('admin.databases.title')</h2>
            <p>@t('admin.databases.setting_description')</p>
        </div>
        <div>
            <a href="{{url('admin/databases/add')}}" class="btn size-s outline">
                @t('admin.databases.add')
            </a>
        </div>
    </div>

    {!! $table !!}
@endpush

@push('footer')
@endpush
