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
        <button data-id="app">@t('admin.settings_bar.system')</button>
        <button data-id="auth">@t('admin.settings_bar.auth')</button>
        <button data-id="database">@t('admin.settings_bar.database')</button>
        <button data-id="lang">@t('admin.settings_bar.language')</button>
        <button data-id="mail">@t('admin.settings_bar.smtp')</button>
        <button data-id="profile">@t('admin.settings_bar.profile')</button>
        <button data-id="lk">@t('admin.settings_bar.lk')</button>
        <button data-id="cache">@t('admin.settings_bar.cache')</button>
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
