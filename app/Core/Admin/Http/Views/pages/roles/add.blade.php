@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.roles.add')]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/roles.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <a class="back-btn" href="{{ url('admin/roles/list') }}">
                <i class="ph ph-arrow-left ignore"></i>
                @t('def.back')
            </a>
            <h2>@t('admin.roles.add_title')</h2>
            <p>@t('admin.roles.add_description')</p>
        </div>
    </div>

    <form id="roleAdd">
        @csrf
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="name">
                    @t('admin.roles.role_label')
                </label>
            </div>
            <div class="col-sm-9">
                <input name="name" id="name" placeholder="@t('admin.roles.role_label')" type="text" class="form-control"
                    required>
            </div>
        </div>
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="name">
                    @t('admin.roles.role_color')
                </label>
            </div>
            <div class="col-sm-9">
                <input name="color" id="color" type="color" class="form-control" required>
            </div>
        </div>
        <div class="position-relative row form-group align-items-start">
            <div class="col-sm-3 col-form-label required">
                <label>@t('admin.roles.permissions')</label>
                <small class="form-text text-muted">@t('admin.roles.perm_desc')</small>
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
    @at('Core/Admin/Http/Views/assets/js/pages/roles/add.js')
@endpush
