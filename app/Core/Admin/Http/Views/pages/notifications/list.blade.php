
@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.notifications.title')]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/notifications.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <h2>@t('admin.notifications.header')</h2>
            <p>@t('admin.notifications.description')</p>
        </div>
        <div>
            <a href="{{url('admin/notifications/add')}}" class="btn size-s outline">
                @t('admin.notifications.add')
            </a>
        </div>
    </div>

    {!! $notifications !!}
@endpush

@push('footer')
    @at('Core/Admin/Http/Views/assets/js/pages/notifications/list.js')
@endpush
