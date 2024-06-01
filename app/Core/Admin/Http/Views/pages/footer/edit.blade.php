@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', [
        'name' => __('admin.footer.edit_title', [
            'name' => $footer->title,
        ]),
    ]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/footer.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <a class="back-btn" href="{{ url('admin/footer/list') }}">
                <i class="ph ph-arrow-left ignore"></i>
                @t('def.back')
            </a>
            <h2>@t('admin.footer.edit_title', [
                'name' => $footer->title,
            ])</h2>
            <p>@t('admin.footer.edit_description')</p>
        </div>
        <div>
            <button data-deleteaction="{{ $footer->id }}" data-deletepath="footer" class="btn size-s error outline">
                @t('def.delete')
            </button>
        </div>
    </div>

    <form id="footEdit">
        @csrf
        <input type="hidden" name="id" value="{{ $footer->id }}">
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="title">
                    @t('admin.footer.footer_label')
                </label>
            </div>
            <div class="col-sm-9">
                <input name="title" id="title" placeholder="@t('admin.footer.footer_label')" type="text" class="form-control"
                    value="{{ $footer->title }}" required>
            </div>
        </div>
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="url">
                    @t('admin.footer.url_label')
                </label>
            </div>
            <div class="col-sm-9">
                <input name="url" id="url" placeholder="@t('admin.footer.url_label')" type="text" class="form-control"
                    value="{{ $footer->url }}">
            </div>
        </div>
        <div class="position-relative row form-group" @if (empty($footer->url)) style="display: none" @endif>
            <div class="col-sm-3 col-form-label">
                <label for="new_tab">
                    @t('admin.footer.new_tab')</label>
                <small>@t('admin.footer.new_tab_description')</small>
            </div>
            <div class="col-sm-9">
                <input name="new_tab" role="switch" id="new_tab" type="checkbox" class="form-check-input"
                    @if ($footer->new_tab) checked @endif>
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
