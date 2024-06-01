@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.navigation.edit')]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/navigation.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <a class="back-btn" href="{{ url('admin/navigation/list') }}">
                <i class="ph ph-arrow-left ignore"></i>
                @t('def.back')
            </a>
            <h2>@t('admin.navigation.edit_title', [
                'name' => $navigation->title,
            ])</h2>
            <p>@t('admin.navigation.edit_description')</p>
        </div>
        <div>
            <button data-deleteaction="{{ $navigation->id }}" data-deletepath="navigation" class="btn size-s error outline">
                @t('def.delete')
            </button>
        </div>
    </div>

    <form id="navEdit">
        @csrf
        <input type="hidden" name="id" value="{{ $navigation->id }}">
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="title">
                    @t('admin.navigation.navigation_label')
                </label>
            </div>
            <div class="col-sm-9">
                <input name="title" id="title" placeholder="@t('admin.navigation.navigation_label')" type="text" class="form-control"
                    value="{{ $navigation->title }}" required>
            </div>
        </div>
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="icon">
                    @t('admin.navigation.icon_label')
                </label>
                <small>@t('admin.navigation.icon_desc')</small>

            </div>
            <div class="col-sm-9">
                <div class="d-flex align-items-center">
                    <div id="icon-output"><i class="{!! $navigation->icon !!}"></i></div>
                    <input name="icon" id="icon" placeholder="@t('admin.navigation.icon_label')" type="text"
                        class="form-control" value="{{ $navigation->icon }}" required>
                </div>
            </div>
        </div>
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="url">
                    @t('admin.navigation.url_label')
                </label>
            </div>
            <div class="col-sm-9">
                <input name="url" id="url" placeholder="@t('admin.navigation.url_label')" type="text" class="form-control"
                    value="{{ $navigation->url }}">
            </div>
        </div>
        <div class="position-relative row form-group" @if (empty($navigation->url)) style="display: none" @endif>
            <div class="col-sm-3 col-form-label">
                <label for="new_tab">
                    @t('admin.navigation.new_tab')</label>
                <small>@t('admin.navigation.new_tab_description')</small>
            </div>
            <div class="col-sm-9">
                <input name="new_tab" role="switch" id="new_tab" type="checkbox" class="form-check-input"
                    @if ($navigation->new_tab) checked @endif>
                <label for="new_tab"></label>
            </div>
        </div>
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="visible_only_for_guests">
                    @t('admin.navigation.visible_only_for_guests')</label>
                <small>@t('admin.navigation.visible_only_for_guests_description')</small>
            </div>
            <div class="col-sm-9">
                <input name="visible_only_for_guests" role="switch" id="visible_only_for_guests" type="checkbox"
                    class="form-check-input" @if ($navigation->visibleOnlyForGuests) checked @endif>
                <label for="visible_only_for_guests"></label>
            </div>
        </div>
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="visible_only_for_logged_in">
                    @t('admin.navigation.visible_only_for_logged_in')</label>
                <small>@t('admin.navigation.visible_only_for_logged_in_description')</small>
            </div>
            <div class="col-sm-9">
                <input name="visible_only_for_logged_in" role="switch" id="visible_only_for_logged_in" type="checkbox"
                    class="form-check-input" @if ($navigation->visibleOnlyForLoggedIn) checked @endif>
                <label for="visible_only_for_logged_in"></label>
            </div>
        </div>
        @if (sizeof($roles) > 0)
            <div class="position-relative row form-group align-items-start">
                <div class="col-sm-3 col-form-label">
                    <label>@t('admin.navigation.roles')</label>
                    <small class="form-text text-muted">@t('admin.navigation.roles_desc')</small>
                </div>
                <div class="col-sm-9">
                    <div class="checkboxes">
                        @foreach ($roles as $role)
                            <div class="form-checkbox">
                                <input class="form-check-input" name="roles[{{ $role->id }}]" type="checkbox"
                                    value="{{ $role->id }}" id="roles[{{ $role->id }}]"
                                    @if ($navigation->hasRole($role)) checked @endif>
                                <label class="form-check-label" for="roles[{{ $role->id }}]"
                                    style="color: {{ $role->color }} !important;">
                                    {{ $role->name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

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
    @at('Core/Admin/Http/Views/assets/js/pages/navigation/add.js')
@endpush
