@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.servers.list')]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/servers.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <h2>@t('admin.servers.header')</h2>
            <p>@t('admin.servers.description')</p>
        </div>
        <div>
            <a href="{{url('admin/servers/add')}}" class="btn size-s outline">
                @t('admin.servers.add')
            </a>
        </div>
    </div>

    {!! $servers !!}
@endpush

@push('footer')
    @at('Core/Admin/Http/Views/assets/js/pages/servers/list.js')
@endpush
