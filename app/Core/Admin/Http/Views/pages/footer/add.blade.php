@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.footer.add')]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/footer.scss')
@endpush

@push('content')
    <div class="admin-header d-flex align-items-center">
        <a href="{{ url('admin/footer/list') }}" class="back_btn">
            <i class="ph ph-caret-left"></i>
        </a>
        <div>
            <h2>@t('admin.footer.add_title')</h2>
            <p>@t('admin.footer.add_description')</p>
        </div>
    </div>

    <form id="add">
        @csrf
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="title">
                    @t('admin.footer.footer_label')
                </label>
            </div>
            <div class="col-sm-9">
                <input name="title" id="title" placeholder="@t('admin.footer.footer_label')" type="text" class="form-control"
                    required>
            </div>
        </div>
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="url">
                    @t('admin.footer.url_label')
                </label>
            </div>
            <div class="col-sm-9">
                <input name="url" id="url" placeholder="@t('admin.footer.url_label')" type="text" class="form-control">
            </div>
        </div>
        <div class="position-relative row form-group" style="display: none">
            <div class="col-sm-3 col-form-label">
                <label for="new_tab">
                    @t('admin.footer.new_tab')</label>
                <small>@t('admin.footer.new_tab_description')</small>
            </div>
            <div class="col-sm-9">
                <input name="new_tab" role="switch" id="new_tab" type="checkbox" class="form-check-input">
                <label for="new_tab"></label>
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
    @at('Core/Admin/Http/Views/assets/js/pages/footer/add.js')
@endpush
