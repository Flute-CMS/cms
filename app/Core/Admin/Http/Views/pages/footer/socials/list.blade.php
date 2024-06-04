@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.footer.social')]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/footer.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <h2>@t('admin.footer.social_header')</h2>
            <p>@t('admin.footer.social_description')</p>
        </div>
        <div>
            <a href="{{ url('admin/footer/socials/add') }}" class="btn size-s outline">
                @t('admin.footer.social_add')
            </a>
        </div>
    </div>

    {!! $socials !!}
@endpush

@push('footer')
    @at('Core/Admin/Http/Views/assets/js/pages/footer/social/list.js')
@endpush
