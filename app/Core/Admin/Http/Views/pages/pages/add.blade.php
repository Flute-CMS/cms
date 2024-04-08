@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', [
        'name' => __('admin.pages.add_title'),
    ]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/pages.scss')
@endpush

@push('content')
    <div class="admin-header d-flex align-items-center">
        <a href="{{ url('admin/pages/list') }}" class="back_btn">
            <i class="ph ph-caret-left"></i>
        </a>
        <div>
            <h2>@t('admin.pages.add_title')</h2>
            <p>@t('admin.pages.add_description')</p>
        </div>
    </div>

    <form data-pagesform="add" data-page="pages">
        @csrf

        <!-- Роут -->
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="route">@t('admin.pages.route_label')</label>
                <small>@t('admin.pages.route_desc')</small>
            </div>
            <div class="col-sm-9">
                <input name="route" id="route" type="text" class="form-control" placeholder="/test"
                    required>
                <div class="error" id="errorMessage"></div>
            </div>
        </div>

        <!-- Заголовок -->
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="title">@t('admin.pages.title_label')</label>
            </div>
            <div class="col-sm-9">
                <input name="title" id="title" type="text" class="form-control" placeholder="some title..."
                    required>
            </div>
        </div>

        <!-- Описание -->
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="description">@t('admin.pages.description_label')</label>
            </div>
            <div class="col-sm-9">
                <textarea name="description" id="description" class="form-control"></textarea>
            </div>
        </div>

        <!-- Ключевые слова -->
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="keywords">@t('admin.pages.keywords_label')</label>
                <small>@t('admin.pages.keywords_desc')</small>
            </div>
            <div class="col-sm-9">
                <input name="keywords" id="keywords" type="text" class="form-control">
            </div>
        </div>

        <!-- Роботы -->
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="robots">@t('admin.pages.robots_label')</label>
                <small>@t('admin.pages.robots_desc')</small>
            </div>
            <div class="col-sm-9">
                <input name="robots" id="robots" type="text" class="form-control"
                    value="all">
            </div>
        </div>

        <!-- OG: Заголовок -->
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="og_title">@t('admin.pages.og_title_label')</label>
            </div>
            <div class="col-sm-9">
                <input name="og_title" id="og_title" type="text" class="form-control">
            </div>
        </div>

        <!-- OG: Описание -->
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="og_description">@t('admin.pages.og_description_label')</label>
            </div>
            <div class="col-sm-9">
                <textarea name="og_description" id="og_description" class="form-control"></textarea>
            </div>
        </div>

        <!-- OG: Изображение -->
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="og_image">@t('admin.pages.og_image_label')</label>
            </div>
            <div class="col-sm-9">
                <input name="og_image" id="og_image" type="text" class="form-control">
            </div>
        </div>

        <div class="position-relative row form-group align-items-start">
            <div class="col-sm-3 col-form-label required">
                <label>
                    @t('admin.pages.content')
                </label>
            </div>
            <div class="col-sm-9">
                <div id="editor"></div>
            </div>
        </div>

        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="permissions">
                    @t('admin.pages.permission_label')</label>
                <small class="form-text text-muted">@t('admin.pages.permission_description')</small>
            </div>
            <div class="col-sm-9">
                <input role="switch" id="permissions" type="checkbox" class="form-check-input">
                <label for="permissions"></label>
            </div>
        </div>

        <div class="position-relative row form-group align-items-start" id="permissions_block" style="display: none">
            <div class="col-sm-3 col-form-label required">
                <label>@t('admin.pages.permissions')</label>
                <small class="form-text text-muted">@t('admin.pages.perm_desc')</small>
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
                                <small>{{ $permission->desc }}</small>
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
    <script src="@asset('assets/js/editor/table.js')"></script>
    <script src="@asset('assets/js/editor/alignment.js')"></script>
    <script src="@asset('assets/js/editor/raw.js')"></script>
    <script src="@asset('assets/js/editor/delimiter.js')"></script>
    <script src="@asset('assets/js/editor/embed.js')"></script>
    <script src="@asset('assets/js/editor/header.js')"></script>
    <script src="@asset('assets/js/editor/image.js')"></script>
    <script src="@asset('assets/js/editor/list.js')"></script>
    <script src="@asset('assets/js/editor/marker.js')"></script>
    @at('Core/Admin/Http/Views/assets/js/editor/additional.js')

    <script>
    </script>

    <script src="@asset('assets/js/editor.js')"></script>

    @at('Core/Admin/Http/Views/assets/js/editor.js')
    @at('Core/Admin/Http/Views/assets/js/pages/pages/add.js')
@endpush
