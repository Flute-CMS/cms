@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.footer.social_add')]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/footer.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <a class="back-btn" href="{{ url('admin/footer/socials/list') }}">
                <i class="ph ph-arrow-left ignore"></i>
                @t('def.back')
            </a>
            <h2>@t('admin.footer.social_add_title')</h2>
            <p>@t('admin.footer.social_add_description')</p>
        </div>
    </div>

    <form id="footerAdds">
        @csrf
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="name">
                    @t('admin.footer.social_footer_label')
                </label>
            </div>
            <div class="col-sm-9">
                <input name="name" id="name" placeholder="@t('admin.footer.social_footer_label')" type="text" class="form-control"
                    required>
            </div>
        </div>
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="icon">
                    @t('admin.footer.social_icon_label')
                </label>
                <small>@t('admin.notifications.icon_desc')</small>
            </div>
            <div class="col-sm-9">
                <div class="d-flex align-items-center">
                    <div id="icon-output"></div>
                    <input name="icon" id="icon" required placeholder="@t('admin.footer.social_icon_label')" type="text"
                        class="form-control">
                </div>
            </div>
        </div>
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="url">
                    @t('admin.footer.social_url_label')
                </label>
            </div>
            <div class="col-sm-9">
                <input name="url" id="url" required placeholder="@t('admin.footer.social_url_label')" type="text"
                    class="form-control">
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
