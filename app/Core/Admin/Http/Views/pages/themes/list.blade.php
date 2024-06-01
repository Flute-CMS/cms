@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.themes.list')]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/themes.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <h2>@t('admin.themes_list.header')</h2>
            <p>@t('admin.themes_list.description')</p>
        </div>
        <div>
            <button class="btn size-s btn--with-icon outline" data-themeinstall>
                @t('admin.themes.install')
                <span class="btn__icon"><i class="ph ph-arrow-line-down"></i></span>
            </button>
        </div>
    </div>

    {!! $themes !!}
@endpush

@push('footer')
    @at('Core/Admin/Http/Views/assets/js/pages/themes.js')
@endpush
