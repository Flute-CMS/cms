@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.composer.title')]),
])

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <h2>@t('admin.composer.title')</h2>
            <p>@t('admin.composer.setting_description')</p>
        </div>
        <div>
            <a href="{{ url('admin/composer/add') }}" class="btn size-s outline" id="add_package">
                @t('admin.composer.add')
            </a>
        </div>
    </div>

    {!! $table !!}
@endpush

@push('footer')
    @at('Core/Admin/Http/Views/assets/js/pages/composer/install.js')

    <script>
        const COMPOSER_PAGE = false;
    </script>

    @if (tip_active('admin_composer'))
        @at('Core/Admin/Http/Views/assets/js/tips/composer.js')
    @endif
@endpush
