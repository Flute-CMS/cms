@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', [
        'name' => __('admin.redirects.edit', [
            'id' => $redirect->id,
        ]),
    ]),
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
            <h2>@t('admin.redirects.edit', [
                'id' => $redirect->id,
            ])</h2>
            <p>@t('admin.redirects.edit_description')</p>
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

    <form id="redirectEdit">
        @csrf
        <input type="hidden" name="id" value="{{ $redirect->id }}">
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="name">
                    @t('admin.redirects.from')
                </label>
            </div>
            <div class="col-sm-9">
                <div class="input-group">
                    <div class="input-group-text">{{ app('app.url') }}</div>
                    <input name="from" id="from" value="{{ $redirect->fromUrl }}" placeholder="@t('admin.redirects.from')"
                        type="text" class="form-control" required>
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
                <input name="to" id="to" value="{{ $redirect->toUrl }}" placeholder="@t('admin.redirects.to')"
                    type="text" class="form-control" required>
            </div>
        </div>

        <div class="position-relative row form-group align-items-start">
            <div class="col-sm-9 offset-sm-3">
                <h3 class="mb-4">@t('admin.redirects.redirect_if')</h3>
                <div class="conditions">
                    @foreach ($redirect->getConditions() as $groupKey => $group)
                        @if (count($group->getConditions()) == 1)
                            @foreach ($group->getConditions() as $conditionKey => $condition)
                                <div class="condition">
                                    @if ($groupKey > 0)
                                        <div class="condition-or" data-translate="def.or"></div>
                                    @endif
                                    <div class="condition-container">
                                        <select class="field">
                                            <option value="ip" {{ $condition->getType() == 'ip' ? 'selected' : '' }}>IP
                                                Address</option>
                                            <option value="cookie"
                                                {{ $condition->getType() == 'cookie' ? 'selected' : '' }}>Cookie</option>
                                            {{-- <option value="country"
                                                {{ $condition->getType() == 'country' ? 'selected' : '' }}>
                                                @t('admin.redirects.country')</option> --}}
                                            <option value="referer"
                                                {{ $condition->getType() == 'referer' ? 'selected' : '' }}>Referer</option>
                                            <option value="request_method"
                                                {{ $condition->getType() == 'request_method' ? 'selected' : '' }}>Request
                                                Method</option>
                                            <option value="user_agent"
                                                {{ $condition->getType() == 'user_agent' ? 'selected' : '' }}>User Agent
                                            </option>
                                            <option value="header"
                                                {{ $condition->getType() == 'header' ? 'selected' : '' }}>Header</option>
                                            <option value="lang" {{ $condition->getType() == 'lang' ? 'selected' : '' }}>
                                                Language</option>
                                        </select>
                                        <select class="operator">
                                            <option value="equals"
                                                {{ $condition->getOperator() == 'equals' ? 'selected' : '' }}>
                                                @t('admin.redirects.equals')
                                            </option>
                                            <option value="not_equals"
                                                {{ $condition->getOperator() == 'not_equals' ? 'selected' : '' }}>
                                                @t('admin.redirects.not_equals')</option>
                                            <option value="contains"
                                                {{ $condition->getOperator() == 'contains' ? 'selected' : '' }}>
                                                @t('admin.redirects.contains')
                                            </option>
                                            <option value="not_contains"
                                                {{ $condition->getOperator() == 'not_contains' ? 'selected' : '' }}>
                                                @t('admin.redirects.not_contains')</option>
                                        </select>
                                        <div>
                                            <div class="input-with-desc">
                                                <input type="text" class="value" value="{{ $condition->getValue() }}"
                                                    data-translate="def.enter_value" data-translate-attribute="placeholder">
                                                <p>@t('admin.redirects.rules.' . $condition->getType())</p>
                                            </div>
                                            <button type="button"
                                                class="btn size-s btn-and outline">@t('def.and')</button>
                                        </div>
                                        @if (!($groupKey === 0 && $conditionKey === 0))
                                            <button type="button" class="btn size-s error btn-remove outline"><i
                                                    class="ph ph-x"></i></button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="condition-group">
                                @foreach ($group->getConditions() as $index => $condition)
                                    <div class="condition {{ $index > 0 ? 'condition-and' : '' }}">
                                        @if ($index == 0 && $groupKey > 0)
                                            <div class="condition-or" data-translate="def.or" data-translate-loaded="true">
                                                @t('def.or')</div>
                                        @endif
                                        <div class="condition-container">
                                            @if ($index > 0)
                                                <div class="field-container">
                                                    <div class="label-and">
                                                        <div class="line"></div>
                                                        <div class="wordwrapper">
                                                            <div class="word" data-translate="def.and"
                                                                data-translate-loaded="true">И</div>
                                                        </div>
                                                    </div>
                                                    <select class="field">
                                                        <option value="ip"
                                                            {{ $condition->getType() == 'ip' ? 'selected' : '' }}>IP
                                                            Address
                                                        </option>
                                                        <option value="cookie"
                                                            {{ $condition->getType() == 'cookie' ? 'selected' : '' }}>
                                                            Cookie
                                                        </option>
                                                        {{-- <option value="country"
                                                            {{ $condition->getType() == 'country' ? 'selected' : '' }}>
                                                            @t('admin.redirects.country')
                                                        </option> --}}
                                                        <option value="referer"
                                                            {{ $condition->getType() == 'referer' ? 'selected' : '' }}>
                                                            Referer
                                                        </option>
                                                        <option value="request_method"
                                                            {{ $condition->getType() == 'request_method' ? 'selected' : '' }}>
                                                            Request Method</option>
                                                        <option value="user_agent"
                                                            {{ $condition->getType() == 'user_agent' ? 'selected' : '' }}>
                                                            User
                                                            Agent</option>
                                                        <option value="header"
                                                            {{ $condition->getType() == 'header' ? 'selected' : '' }}>
                                                            Header
                                                        </option>
                                                        <option value="lang"
                                                            {{ $condition->getType() == 'lang' ? 'selected' : '' }}>
                                                            Language
                                                        </option>
                                                    </select>
                                                </div>
                                            @else
                                                <select class="field">
                                                    <option value="ip"
                                                        {{ $condition->getType() == 'ip' ? 'selected' : '' }}>IP Address
                                                    </option>
                                                    <option value="cookie"
                                                        {{ $condition->getType() == 'cookie' ? 'selected' : '' }}>Cookie
                                                    </option>
                                                    <option value="country"
                                                        {{ $condition->getType() == 'country' ? 'selected' : '' }}>Country
                                                    </option>
                                                    <option value="referer"
                                                        {{ $condition->getType() == 'referer' ? 'selected' : '' }}>Referer
                                                    </option>
                                                    <option value="request_method"
                                                        {{ $condition->getType() == 'request_method' ? 'selected' : '' }}>
                                                        Request Method</option>
                                                    <option value="user_agent"
                                                        {{ $condition->getType() == 'user_agent' ? 'selected' : '' }}>User
                                                        Agent</option>
                                                    <option value="header"
                                                        {{ $condition->getType() == 'header' ? 'selected' : '' }}>Header
                                                    </option>
                                                    <option value="lang"
                                                        {{ $condition->getType() == 'lang' ? 'selected' : '' }}>Language
                                                    </option>
                                                </select>
                                            @endif
                                            <select class="operator">
                                                <option value="equals"
                                                    {{ $condition->getOperator() == 'equals' ? 'selected' : '' }}>
                                                    @t('admin.redirects.equals')
                                                </option>
                                                <option value="not_equals"
                                                    {{ $condition->getOperator() == 'not_equals' ? 'selected' : '' }}>
                                                    @t('admin.redirects.not_equals')</option>
                                                <option value="contains"
                                                    {{ $condition->getOperator() == 'contains' ? 'selected' : '' }}>
                                                    @t('admin.redirects.contains')
                                                </option>
                                                <option value="not_contains"
                                                    {{ $condition->getOperator() == 'not_contains' ? 'selected' : '' }}>
                                                    @t('admin.redirects.not_contains')</option>
                                            </select>
                                            <div>
                                                <div class="input-with-desc">
                                                    <input type="text" class="value"
                                                        value="{{ $condition->getValue() }}"
                                                        data-translate="def.enter_value"
                                                        data-translate-attribute="placeholder">
                                                    <p>@t('admin.redirects.rules.' . $condition->getType())</p>
                                                </div>
                                                <button type="button"
                                                    class="btn size-s btn-and outline">@t('def.and')</button>
                                                <button type="button"
                                                    class="btn size-s btn-or outline">@t('def.or')</button>
                                            </div>
                                            @if (!($groupKey === 0 && $index === 0))
                                                <button type="button" class="btn size-s error btn-remove outline"><i
                                                        class="ph ph-x"></i></button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
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
