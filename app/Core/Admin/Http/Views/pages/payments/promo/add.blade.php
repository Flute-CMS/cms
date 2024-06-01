@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.payments.promo.add')]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/payments.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <a class="back-btn" href="{{ url('admin/payments/promo/list') }}">
                <i class="ph ph-arrow-left ignore"></i>
                @t('def.back')
            </a>
            <h2>@t('admin.payments.promo.add_title')</h2>
            <p>@t('admin.payments.promo.add_description')</p>
        </div>
    </div>

    <form id="promoAdd">
        @csrf
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="code">
                    @t('admin.payments.promo.name_label')
                </label>
            </div>
            <div class="col-sm-9">
                <input name="code" id="code" placeholder="@t('admin.payments.promo.name_label')" type="text" class="form-control"
                    required>
            </div>
        </div>
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="max_usages">
                    @t('admin.payments.promo.max_usages_label')
                </label>
            </div>
            <div class="col-sm-9">
                <input name="max_usages" id="max_usages" placeholder="@t('admin.payments.promo.max_usages')" type="number" class="form-control"
                    required>
            </div>
        </div>
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="type">
                    @t('admin.payments.promo.type_label')
                </label>
            </div>
            <div class="col-sm-9">
                <select name="type" id="type" class="form-control">
                    <option value="amount">@t('admin.payments.promo.amount')</option>
                    <option value="percentage">@t('admin.payments.promo.percentage')</option>
                    {{-- <option value="subtract">@t('admin.payments.promo.subtract')</option> --}}
                </select>
            </div>
        </div>
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="value">
                    @t('admin.payments.promo.value_label')
                </label>
            </div>
            <div class="col-sm-9">
                <input name="value" id="value" placeholder="@t('admin.payments.promo.value_label')" type="number" class="form-control"
                    required>
            </div>
        </div>
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="expires_at">
                    @t('admin.payments.promo.expires_at')
                </label>
            </div>
            <div class="col-sm-9">
                <input name="expires_at" id="expires_at" placeholder="@t('admin.payments.promo.expires_at')" type="datetime-local"
                    class="form-control" required>
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
    @at('Core/Admin/Http/Views/assets/js/pages/payments/promo/add.js')
@endpush
