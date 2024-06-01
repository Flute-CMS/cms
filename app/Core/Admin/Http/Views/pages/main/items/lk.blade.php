<form>
    @csrf

    {{-- <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label required">
            <label for="name">
                @t('admin.lk.min_label')
            </label>
            <small class="form-text text-muted">@t('admin.lk.min_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="min_amount" id="min_amount" placeholder="@t('admin.lk.min_label')" type="number" class="form-control"
                value="{{ config('lk.min_amount') }}" required>
        </div>
    </div> --}}

    <!-- Валюта -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label required">
            <label for="currency_view">
                @t('admin.lk.currency_view_label')
            </label>
            <small class="form-text text-muted">@t('admin.lk.currency_view_description')</small>
        </div>
        <div class="col-sm-9">
            <input required name="currency_view" id="currency_view" placeholder="@t('admin.lk.currency_label')" type="text"
                class="form-control" value="{{ config('lk.currency_view') }}">
        </div>
    </div>

    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label">
            <label for="pay_in_new_window">
                <div class="may_unstable" data-tooltip="@t('admin.may_have_errors')" data-tooltip-conf="right multiline"
                    data-faq="@t('admin.what_it_means')" data-faq-content="@t('admin.lk.new_window_warning')">
                    <i class="ph ph-warning"></i>
                </div>
                @t('admin.lk.in_new_window')
            </label>
            <small class="form-text text-muted">@t('admin.lk.in_new_window_desc')</small>
        </div>
        <div class="col-sm-9">
            <input name="pay_in_new_window" role="switch" id="pay_in_new_window" type="checkbox"
                class="form-check-input" {{ config('lk.pay_in_new_window') ? 'checked' : '' }}>
            <label for="pay_in_new_window"></label>
        </div>
    </div>

    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label">
            <label for="oferta_view">
                @t('admin.lk.oferta')</label>
            <small class="form-text text-muted">@t('admin.lk.oferta_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="oferta_view" role="switch" id="oferta_view" type="checkbox" class="form-check-input"
                {{ config('lk.oferta_view') ? 'checked' : '' }}>
            <label for="oferta_view"></label>
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
