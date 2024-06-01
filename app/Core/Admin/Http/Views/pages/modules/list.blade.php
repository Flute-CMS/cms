@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.modules.list')]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/modules.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <h2>@t('admin.modules_list.header')</h2>
            <p>@t('admin.modules_list.description')</p>
        </div>
        <div>
            <button class="btn size-s btn--with-icon outline" data-moduleinstall>
                @t('admin.modules_list.module_install')
                <span class="btn__icon"><i class="ph ph-arrow-line-down"></i></span>
            </button>
        </div>
    </div>

    {!! $modules !!}
@endpush

@push('footer')
    @at('Core/Admin/Http/Views/assets/js/pages/modules.js')
@endpush
