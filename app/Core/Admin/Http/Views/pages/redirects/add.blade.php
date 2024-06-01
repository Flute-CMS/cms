@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.redirects.add')]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/redirects.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <a class="back-btn" href="{{ url('admin/redirects/list') }}">
                <i class="ph ph-arrow-left ignore"></i>
                @t('def.back')
            </a>
            <h2>@t('admin.redirects.add_title')</h2>
            <p>@t('admin.redirects.add_description')</p>
        </div>
    </div>

    <div class="position-relative row form-group">
        <div class="col-sm-12">
            <div class="admin-notification">
                <div class="admin-notification-content">
                    <i class="ph ph-warning-circle"></i>
                    <div>
                        <h4>@t('def.warning')!</h4>
                        <p>@t('admin.redirects.warning')</p>
                    </div>
                </div>
                <a data-faq="@t('admin.redirects.faq.title')" data-faq-content="@t('admin.redirects.faq.content')">@t('def.learn_more')</a>
            </div>
        </div>
    </div>

    <form id="redirectAdd">
        @csrf
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="name">
                    @t('admin.redirects.from')
                </label>
            </div>
            <div class="col-sm-9">
                <div class="input-group">
                    <div class="input-group-text">{{ app('app.url') }}</div>
                    <input name="from" id="from" placeholder="@t('admin.redirects.from')" type="text"
                        class="form-control" required>
                </div>
            </div>
        </div>
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="name">
                    @t('admin.redirects.to')
                </label>
            </div>
            <div class="col-sm-9">
                <input name="to" id="to" placeholder="@t('admin.redirects.to')" type="text" class="form-control"
                    required>
            </div>
        </div>

        <div class="position-relative row form-group align-items-start">
            <div class="col-sm-9 offset-sm-3">
                <h3 class="mb-4">@t('admin.redirects.redirect_if')</h3>
                <div class="conditions">
                    <div class="condition">
                        <div class="condition-container">
                            <select class="field">
                                <option value="ip" selected>IP Address</option>
                                <option value="cookie">Cookie</option>
                                {{-- <option value="country">@t('admin.redirects.country')</option> --}}
                                <option value="referer">Referer</option>
                                <option value="request_method">Request Method</option>
                                <option value="user_agent">User Agent</option>
                                <option value="header">Header</option>
                                <option value="lang">@t('admin.redirects.lang')</option>
                            </select>
                            <select class="operator">
                                <option value="equals">@t('admin.redirects.equals')</option>
                                <option value="not_equals">@t('admin.redirects.not_equals')</option>
                                <option value="contains">@t('admin.redirects.contains')</option>
                                <option value="not_contains">@t('admin.redirects.not_contains')</option>
                            </select>
                            <div>
                                <div class="input-with-desc">
                                    <input type="text" class="value" data-translate="def.enter_value"
                                        data-translate-attribute="placeholder">
                                    <p>@t('admin.redirects.rules.ip')</p>
                                </div>
                                <button type="button" class="btn size-s btn-and outline">@t('def.and')</button>
                                <button type="button" class="btn size-s btn-or outline">@t('def.or')</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="enabled">
                    @t('admin.redirects.enabled')</label>
                <small>@t('admin.redirects.enabled_description')</small>
            </div>
            <div class="col-sm-9">
                <input name="enabled" checked role="switch" id="enabled" type="checkbox" class="form-check-input">
                <label for="enabled"></label>
            </div>
        </div> --}}

        <!-- Кнопка отправки -->
        <div class="position-relative row form-check">
            <div class="col-sm-9 offset-sm-3">
                <button type="submit" class="btn size-m btn--with-icon primary">
                    @t('def.save')
                    <span class="btn__icon arrow"><i class="ph ph-arrow-right"></i></span>
                </button>
            </div>
        </div>
    </form>
@endpush

@push('footer')
    @at('Core/Admin/Http/Views/assets/js/pages/redirects/add.js')
@endpush
