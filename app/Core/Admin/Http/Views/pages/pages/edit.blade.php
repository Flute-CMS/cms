@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', [
        'name' => __('admin.pages.edit_title', [
            ':name' => $page->title,
        ]),
    ]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/pages.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <a class="back-btn" href="{{ url('admin/pages/list') }}">
                <i class="ph ph-arrow-left ignore"></i>
                @t('def.back')
            </a>
            <h2>@t('admin.pages.edit_title', [
                ':name' => $page->title,
            ])</h2>
            <p>@t('admin.pages.edit_description')</p>
        </div>
        <div>
            @if ($page->route !== '/')
                <button data-deleteaction="{{ $page->id }}" data-deletepath="pages" class="btn size-s error outline">
                    @t('def.delete')
                </button>
            @endif
            <a href="{{ url($page->route) }}" class="btn btn--with-icon size-s outline ignore" target="_blank">
                @t('def.goto') 
                <span class="btn__icon arrow"><i class="ph ph-arrow-right"></i></span>
            </a>
        </div>
    </div>

    <form data-pagesform="edit" data-page="pages" data-id="{{ $page->id }}">
        @csrf
        <input type="hidden" name="id" value="{{ $page->id }}">

        <!-- Роут -->
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="route">@t('admin.pages.route_label')</label>
                <small>@t('admin.pages.route_desc')</small>
            </div>
            <div class="col-sm-9">
                <div class="input-group">
                    <div class="input-group-text">{{ app('app.url') }}</div>
                    <input name="route" id="route" type="text" class="form-control" value="{{ $page->route }}"
                        required>
                </div>
                <div class="error" id="errorMessage"></div>
            </div>
        </div>

        <!-- Заголовок -->
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="title">@t('admin.pages.title_label')</label>
            </div>
            <div class="col-sm-9">
                <input name="title" id="title" type="text" class="form-control" value="{{ $page->title }}"
                    required>
            </div>
        </div>

        <!-- Описание -->
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="description">@t('admin.pages.description_label')</label>
            </div>
            <div class="col-sm-9">
                <textarea name="description" id="description" class="form-control">{{ $page->description }}</textarea>
            </div>
        </div>

        <!-- Ключевые слова -->
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="keywords">@t('admin.pages.keywords_label')</label>
                <small>@t('admin.pages.keywords_desc')</small>
            </div>
            <div class="col-sm-9">
                <input name="keywords" id="keywords" type="text" class="form-control" value="{{ $page->keywords }}">
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
                    value="{{ $page->robots ?? 'all' }}">
            </div>
        </div>

        <!-- OG: Заголовок -->
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="og_title">@t('admin.pages.og_title_label')</label>
            </div>
            <div class="col-sm-9">
                <input name="og_title" id="og_title" type="text" class="form-control" value="{{ $page->og_title }}">
            </div>
        </div>

        <!-- OG: Описание -->
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="og_description">@t('admin.pages.og_description_label')</label>
            </div>
            <div class="col-sm-9">
                <textarea name="og_description" id="og_description" class="form-control">{{ $page->og_description }}</textarea>
            </div>
        </div>

        <!-- OG: Изображение -->
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="og_image">@t('admin.pages.og_image_label')</label>
            </div>
            <div class="col-sm-9">
                <input name="og_image" id="og_image" type="text" class="form-control" value="{{ $page->og_image }}">
            </div>
        </div>

        <div class="position-relative row form-group align-items-start">
            <div class="col-sm-3 col-form-label required">
                <label>
                    @t('admin.pages.content')
                </label>
            </div>
            <div class="col-sm-9">
                <div data-editorjs id="editorPageEdit-{{ $page->id }}"></div>
            </div>
        </div>

        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="permissions">
                    @t('admin.pages.permission_label')</label>
                <small class="form-text text-muted">@t('admin.pages.permission_description')</small>
            </div>
            <div class="col-sm-9">
                <input role="switch" id="permissions" type="checkbox" class="form-check-input"
                    @if (sizeof($page->permissions) > 0) checked @endif>
                <label for="permissions"></label>
            </div>
        </div>

        <div class="position-relative row form-group align-items-start" id="permissions_block"
            @if (sizeof($page->permissions) === 0) style="display: none" @endif>
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
                                value="{{ $permission->id }}" id="permissions[{{ $permission->id }}]"
                                @if ($page->hasPermission($permission)) checked @endif>
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

    <script data-loadevery>
        window.defaultEditorData['editorPageEdit-{{ $page->id }}'] = {
            blocks: {!! $blocks ?? '[]' !!}
        };
    </script>

    <script src="@asset('assets/js/editor.js')"></script>

    @at('Core/Admin/Http/Views/assets/js/editor.js')
    @at('Core/Admin/Http/Views/assets/js/pages/pages/add.js')
@endpush
