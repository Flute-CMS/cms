@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', [
        'name' => __('admin.api.add_title'),
    ]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/api.scss')
@endpush

@push('content')
    <div class="admin-header d-flex align-items-center">
        <a href="{{ url('admin/api/list') }}" class="back_btn">
            <i class="ph ph-caret-left"></i>
        </a>
        <div>
            <h2>@t('admin.api.add_title')</h2>
            <p>@t('admin.api.add_description')</p>
        </div>
    </div>

    <form data-form="add" data-page="api">
        @csrf

        <!-- Роут -->
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="key">@t('admin.api.key')</label>
            </div>
            <div class="col-sm-9">
                <input name="key" id="key" type="text" class="form-control" readonly
                    value="{{ $random }}">
                <a id="regenerate-btn" type="button" class="form-text">@t('admin.api.regenerate')</a>
            </div>
        </div>

        <div class="position-relative row form-group align-items-start" id="permissions_block">
            <div class="col-sm-3 col-form-label required">
                <label>@t('admin.api.permissions')</label>
                <small class="form-text text-muted">@t('admin.api.perm_desc')</small>
            </div>
            <div class="col-sm-9">
                <div class="checkboxes">
                    @foreach ($permissions as $permission)
                        @if (!user()->hasPermission($permission->name) && !user()->hasPermission('admin.boss'))
                            @continue
                        @endif
                        <div class="form-checkbox">
                            <input class="form-check-input" name="permissions[{{ $permission->id }}]" type="checkbox"
                                value="{{ $permission->id }}" id="permissions[{{ $permission->id }}]">
                            <label class="form-check-label" for="permissions[{{ $permission->id }}]">
                                {{ $permission->name }}
                                <small>{{ __($permission->desc) }}</small>
                            </label>
                        </div>
                    @endforeach
                </div>
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
    @at('Core/Admin/Http/Views/assets/js/pages/api.js')
@endpush
