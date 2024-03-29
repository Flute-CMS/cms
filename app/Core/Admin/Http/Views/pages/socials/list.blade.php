
@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.socials.list')]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/socials.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <h2>@t('admin.socials.header')</h2>
            <p>@t('admin.socials.description')</p>
        </div>
        <div>
            <a href="{{url('admin/socials/add')}}" class="btn size-s outline">
                @t('admin.socials.add')
            </a>
        </div>
    </div>

    {!! $socials !!}
@endpush

@push('footer')
    @at('Core/Admin/Http/Views/assets/js/pages/socials/list.js')
@endpush
