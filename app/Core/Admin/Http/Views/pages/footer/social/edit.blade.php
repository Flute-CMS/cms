@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', [
        'name' => __('admin.footer.social_edit_title', [
            'name' => $item->name,
        ]),
    ]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/footer.scss')
@endpush

@push('content')
    <div class="admin-header d-flex align-items-center">
        <a href="{{ url('admin/footer/socials/list') }}" class="back_btn">
            <i class="ph ph-caret-left"></i>
        </a>
        <div>
            <h2>@t('admin.footer.social_edit_title', [
                'name' => $item->name,
            ])</h2>
            <p>@t('admin.footer.social_edit_description')</p>
        </div>
    </div>

    <form id="edit">
        @csrf
        <input type="hidden" name="id" value="{{ $item->id }}">
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="name">
                    @t('admin.footer.social_footer_label')
                </label>
            </div>
            <div class="col-sm-9">
                <input name="name" id="name" placeholder="@t('admin.footer.social_footer_label')" type="text" class="form-control"
                    required value="{{ $item->name }}">
            </div>
        </div>
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="icon">
                    @t('admin.footer.social_icon_label')
                </label>
            </div>
            <div class="col-sm-9">
                <input name="icon" value="{{ $item->icon }}" id="icon" placeholder="@t('admin.footer.social_icon_label')"
                    type="text" class="form-control" required>
            </div>
        </div>
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="url">
                    @t('admin.footer.social_url_label')
                </label>
            </div>
            <div class="col-sm-9">
                <input name="url" value="{{ $item->url }}" id="url" placeholder="@t('admin.footer.social_url_label')"
                    type="text" class="form-control" required>
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
    @at('Core/Admin/Http/Views/assets/js/pages/footer/social/add.js')
@endpush
