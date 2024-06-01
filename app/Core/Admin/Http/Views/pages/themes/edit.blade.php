@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', [
        'name' => __('admin.themes_list.edit', [
            ':theme' => $theme,
        ]),
    ]),
])

@push('header')
    <link rel="stylesheet" href="@asset('assets/css/libs/picker.css')" />

    @at('Core/Admin/Http/Views/assets/styles/pages/themes.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <a class="back-btn" href="{{ url('admin/socials/list') }}">
                <i class="ph ph-arrow-left ignore"></i>
                @t('def.back')
            </a>
            <h2>@t('admin.themes_list.edit', [
                ':theme' => $theme,
            ])</h2>
            <p>@t('admin.themes_list.edit_description')</p>
        </div>
        <div>
            <button class="btn size-s primary" data-savetheme="{{ $theme }}">
                @t('def.save')
            </button>
        </div>
    </div>

    <div class="theme-editor" id="theme-editor">
        <div class="theme-preview">
            <div class="preview-content container">
                <div class="nav-block"></div>
                <div class="content-block">
                    <div class="row gx-3 gy-5">
                        <div class="col-md-4">
                            <div class="test-card">
                                <h2>@t('admin.themes_list.preview.card_title')</h2>
                                <p>@t('admin.themes_list.preview.card_desc')</p>
                                <div class="test-card-content">
                                    <p>@t('admin.themes_list.preview.card_inverse')</p>
                                </div>
                                <div class="test-card-buttons">
                                    <button class="card-button secondary">@t('admin.themes_list.preview.secondary_btn')</button>
                                    <button class="card-button">@t('admin.themes_list.preview.main_btn')</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="test-card">
                                <h2>@t('admin.themes_list.preview.card_title')</h2>
                                <p>@t('admin.themes_list.preview.card_desc')</p>
                                <div class="test-card-content">
                                    <p>@t('admin.themes_list.preview.card_inverse')</p>
                                </div>
                                <div class="test-card-buttons">
                                    <button class="card-button secondary">@t('admin.themes_list.preview.secondary_btn')</button>
                                    <button class="card-button">@t('admin.themes_list.preview.main_btn')</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="test-card">
                                <h2>@t('admin.themes_list.preview.card_title')</h2>
                                <p>@t('admin.themes_list.preview.card_desc')</p>
                                <div class="test-card-content">
                                    <p>@t('admin.themes_list.preview.card_inverse')</p>
                                </div>
                                <div class="test-card-buttons">
                                    <button class="card-button secondary">@t('admin.themes_list.preview.secondary_btn')</button>
                                    <button class="card-button">@t('admin.themes_list.preview.main_btn')</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 not-active">
                            <h1>@t('admin.themes_list.preview.not_active_elements')</h1>
                            <div class="not-active-el">
                                <div class="test-card">
                                    <p>@t('admin.themes_list.preview.not_active_el')</p>
                                    <p class="disabled-el">@t('admin.themes_list.preview.disabled_el')</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <h1>@t('admin.themes_list.preview.flash')</h1>
                            <div class="flashes">
                                <div class="alert alert-success">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <circle cx="12" cy="12" r="9" />
                                        <path d="M9 12l2 2l4 -4" />
                                    </svg>
                                    <div>
                                        Test message
                                    </div>
                                </div>

                                <div class="alert alert-error">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <circle cx="12" cy="12" r="9" />
                                        <line x1="12" y1="8" x2="12" y2="12" />
                                        <line x1="12" y1="16" x2="12.01" y2="16" />
                                    </svg>
                                    <div>
                                        Test message
                                    </div>
                                </div>

                                <div class="alert alert-warning">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <circle cx="12" cy="12" r="9" />
                                        <line x1="12" y1="10" x2="12" y2="14" />
                                        <line x1="12" y1="16" x2="12.01" y2="16" />
                                    </svg>
                                    <div>
                                        Test message
                                    </div>
                                </div>

                                <div class="alert alert-info">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <circle cx="12" cy="12" r="9" />
                                        <line x1="12" y1="16" x2="12" y2="12" />
                                        <line x1="12" y1="8" x2="12.01" y2="8" />
                                    </svg>
                                    <div>
                                        Test message
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="footer-block"></div>
            </div>
        </div>
        <div class="theme-controls">
            <div class="theme-controls-control range-control">
                <label for="transition">{{ __('admin.themes_list.vars.transition') }}</label>
                <input type="range" id="transition" name="transition" min="0.1" max="2" step="0.1"
                    value="{{ str_replace('s', '', $vars['transition']) }}">
                <span id="transition-value">{{ str_replace('s', '', $vars['transition']) }}s</span>
            </div>
            @foreach ($vars as $var => $value)
                @if (str_contains($var, 'border-radius'))
                    <div class="theme-controls-control range-control">
                        <label for="{{ $var }}">{{ __('admin.themes_list.vars.' . $var) }}</label>
                        <input type="range" id="{{ $var }}" name="{{ $var }}" min="0"
                            max="30" step="1" value="{{ str_replace('px', '', $value) }}">
                        <span id="{{ $var }}-value">{{ str_replace('px', '', $value) }}px</span>
                    </div>
                @endif
            @endforeach
            @foreach ($vars as $var => $value)
                @if (str_contains($var, 'color'))
                    <div class="theme-controls-control">
                        <label for="{{ $var }}">{{ __('admin.themes_list.vars.' . $var) }}</label>
                        <div class="color-picker" id="{{ $var }}-picker"></div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@endpush

@push('footer')
    <script src="@asset('assets/js/picker.js')" defer></script>

    @at('Core/Admin/Http/Views/assets/js/pages/theme_edit.js')
    <script>
        @foreach ($vars as $var => $value)
            @if (str_contains($var, 'border-radius'))
                $(document).on('input', '#{{ $var }}', function() {
                    let $this = $(this).closest('.range-control');
                    updateValue($this, '{{ $var }}', this.value);
                });
            @endif
        @endforeach
    </script>
    <style>
        @foreach ($vars as $var => $value)
            :root {
                --{{ $var }}: {{ $value }};
            }
        @endforeach
    </style>
@endpush
