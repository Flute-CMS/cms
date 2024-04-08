@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.settings')]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/main.scss')
@endpush

@push('content')
    <div class="admin-header">
        <h2>@t('admin.main_settings.header')</h2>
        <p>@t('admin.main_settings.description')</p>
    </div>

    <div class="settings_bar">
        <button data-id="app">
            <i class="ph ph-gear-fine"></i>
            @t('admin.settings_bar.system')
        </button>
        <button data-id="auth">
            <i class="ph ph-fingerprint-simple"></i>
            @t('admin.settings_bar.auth')
        </button>
        <button data-id="database">
            <i class="ph ph-database"></i>
            @t('admin.settings_bar.database')
        </button>
        <button data-id="lang">
            <i class="ph ph-translate"></i>
            @t('admin.settings_bar.language')
        </button>
        <button data-id="mail">
            <i class="ph ph-envelope-simple"></i>
            @t('admin.settings_bar.smtp')
        </button>
        <button data-id="profile">
            <i class="ph ph-user-circle"></i>
            @t('admin.settings_bar.profile')
        </button>
        <button data-id="lk">
            <i class="ph ph-currency-circle-dollar"></i>
            @t('admin.settings_bar.lk')
        </button>
        <button data-id="cache">
            <i class="ph ph-cloud"></i>
            @t('admin.settings_bar.cache')
        </button>
    </div>

    <div class="settings-container">
        <div id="app">
            @include('Core.Admin.Http.Views.pages.main.items.app')
        </div>
        <div id="auth">
            @include('Core.Admin.Http.Views.pages.main.items.auth')
        </div>
        <div id="database">
            @include('Core.Admin.Http.Views.pages.main.items.database')
        </div>
        <div id="lang">
            @include('Core.Admin.Http.Views.pages.main.items.lang')
        </div>
        <div id="mail">
            @include('Core.Admin.Http.Views.pages.main.items.mail')
        </div>
        <div id="profile">
            @include('Core.Admin.Http.Views.pages.main.items.profile')
        </div>
        <div id="lk">
            @include('Core.Admin.Http.Views.pages.main.items.lk')
        </div>
        <div id="cache">
            @include('Core.Admin.Http.Views.pages.main.items.cache')
        </div>
    </div>
@endpush

@push('footer')
    @at('Core/Admin/Http/Views/assets/js/pages/main.js')

    @if (tip_active('admin_settings'))
        @at('Core/Admin/Http/Views/assets/js/tips/settings.js')
    @endif
@endpush
