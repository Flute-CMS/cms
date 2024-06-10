@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', [
        'name' => __('admin.socials.edit_title', [
            'name' => $social->key,
        ]),
    ]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/socials.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <a class="back-btn" href="{{ url('admin/socials/list') }}">
                <i class="ph ph-arrow-left ignore"></i>
                @t('def.back')
            </a>
            <h2>@t('admin.socials.edit_title', [
                'name' => $social->key,
            ])</h2>
            <p>@t('admin.socials.edit_description')</p>
        </div>
        <div>
            <button data-deleteaction="{{ $social->id }}" data-deletepath="socials" class="btn size-s error outline">
                @t('def.delete')
            </button>
        </div>
    </div>

    <form id="socialEdit">
        @csrf
        <input type="hidden" name="id" value="{{ $social->id }}">
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="key">
                    @t('admin.socials.social_label')
                </label>
            </div>
            <div class="col-sm-9">
                <select name="key" id="key" class="form-control">
                    @foreach ($drivers as $item)
                        <option value="{{ $item }}" @if ($social->key === $item) selected @endif>
                            {{ $item }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="icon">@t('admin.socials.social_icon_label')</label>
                <small>@t('admin.notifications.icon_desc')</small>
            </div>
            <div class="col-sm-9">
                <div class="d-flex align-items-center">
                    <div id="icon-output">{!! $social->icon !!}</div>
                    <input name="icon" id="icon" placeholder="@t('admin.socials.social_icon_label')" type="text" class="form-control"
                        value="{{ $social->icon }}" required>
                </div>
            </div>
        </div>

        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="cooldownTime">
                    @t('admin.socials.cooldown_label')
                </label>
                <small>@t('admin.socials.cooldown_desc')</small>
            </div>
            <div class="col-sm-9">
                <div class="d-flex align-items-center">
                    <input name="cooldownTime" id="cooldownTime" placeholder="@t('admin.socials.cooldown_label')" type="number"
                        class="form-control" value="{{ $social->cooldownTime }}" required>
                </div>
            </div>
        </div>

        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="settings">
                    @t('admin.socials.settings_label')
                </label>
            </div>
            <div class="col-sm-9">
                <div id="editorSocial" class="editor-ace">{!! $social->settings !!}</div>
            </div>
        </div>

        <!-- Readonly inputs for redirect_uris -->
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="redirectUri1">Redirect URI 1</label>
            </div>
            <div class="col-sm-9" data-tooltip="@t('def.copy')" data-tooltip-conf="top">
                <input id="redirectUri1" type="text" class="form-control" readonly
                    value="{{ url('social/' . $social->key) }}"
                    data-copy="{{ url('social/' . $social->key) }}">
            </div>
        </div>
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="redirectUri2">Redirect URI 2</label>
            </div>
            <div class="col-sm-9" data-tooltip="@t('def.copy')" data-tooltip-conf="top">
                <input id="redirectUri2" type="text" class="form-control" readonly 
                    value="{{ url('profile/social/bind/' . $social->key) }}"
                    data-copy="{{ url('profile/social/bind/' . $social->key) }}">
            </div>
        </div>

        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="allowToRegister">
                    @t('admin.socials.allow_to_register')</label>
                <small>@t('admin.socials.allow_to_register_description')</small>
            </div>
            <div class="col-sm-9">
                <input name="allowToRegister" @if ($social->allowToRegister) checked @endif role="switch"
                    id="allowToRegister" type="checkbox" class="form-check-input">
                <label for="allowToRegister"></label>
            </div>
        </div>

        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="enabled">
                    @t('admin.socials.enabled')</label>
                <small>@t('admin.socials.enabled_description')</small>
            </div>
            <div class="col-sm-9">
                <input name="enabled" @if ($social->enabled) checked @endif role="switch" id="enabled"
                    type="checkbox" class="form-check-input">
                <label for="enabled"></label>
            </div>
        </div>

        <!-- Кнопка отправки -->
        <div class="position-relative row form-check">
            <div class="col-sm-9 offset-sm-3">
                <button type="submit" data-save class="btn size-m btn--with-icon primary">
                    @t('def.save')
                    <span class="btn__icon arrow"><i class="ph ph-arrow-right"></i></span>
                </button>
            </div>
        </div>
    </form>
@endpush

@push('footer')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/js-beautify/1.15.1/beautify.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.12/ace.js" type="text/javascript" charset="utf-8"></script>

    @at('Core/Admin/Http/Views/assets/js/pages/socials/add.js')
@endpush
