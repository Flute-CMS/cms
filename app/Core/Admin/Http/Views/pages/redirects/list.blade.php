@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.redirects.title')]),
])

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <h2>@t('admin.redirects.title')</h2>
            <p>@t('admin.redirects.description')</p>
        </div>
        <div>
            <a href="{{url('admin/redirects/add')}}" class="btn size-s outline">
                @t('admin.redirects.add')
            </a>
        </div>
    </div>

    {!! $redirects !!}
@endpush