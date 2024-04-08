@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.composer.add')]),
])

@push('content')
    <div class="admin-header d-flex align-items-center">
        <a href="{{ url('admin/composer/list') }}" class="back_btn">
            <i class="ph ph-caret-left"></i>
        </a>
        <div>
            <h2>@t('admin.composer.add')</h2>
            <p>@t('admin.composer.add_description')</p>
        </div>
    </div>

    {!! $composerTable !!}
@endpush

@push('footer')
    @at('Core/Admin/Http/Views/assets/js/pages/composer/install.js')

    <script>
        const COMPOSER_PAGE = true;
    </script>

    @if (tip_active('admin_composer'))
        @at('Core/Admin/Http/Views/assets/js/tips/composer.js')
    @endif
@endpush
