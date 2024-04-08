@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.currency.add')]),
])

@push('content')
    <div class="admin-header d-flex align-items-center">
        <a href="{{ url('admin/currency/list') }}" class="back_btn">
            <i class="ph ph-caret-left"></i>
        </a>
        <div>
            <h2>@t('admin.currency.add')</h2>
            <p>@t('admin.currency.add_description')</p>
        </div>
    </div>

    <form data-form="add" data-page="currency" enctype="multipart/form-data">
        @csrf
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="currency">@t('admin.currency.currency')</label>
            </div>
            <div class="col-sm-9">
                <input type="text" id="currency" name="currency" placeholder="RUB" required>
            </div>
        </div>

        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="minimum_value">@t('admin.currency.min_value')</label>
                <small class="form-text text-muted">@t('admin.currency.min_value_desc')</small>
            </div>
            <div class="col-sm-9">
                <input type="number" step="0.01" id="minimum_value" name="minimum_value" placeholder="10.00"
                    value="1.00" required>
            </div>
        </div>
        
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="exchange_rate">@t('admin.currency.exchange_rate')</label>
                <small class="form-text text-muted">@t('admin.currency.exchange_rate_desc')</small>
            </div>
            <div class="col-sm-9">
                <input type="number" step="0.001" id="exchange_rate" name="exchange_rate" placeholder="10.00"
                    value="1.00" required>
            </div>
        </div>

        <div class="position-relative row form-group align-items-start">
            <div class="col-sm-3 col-form-label required">
                <label>@t('admin.currency.gateway')</label>
            </div>
            <div class="col-sm-9">
                <div class="checkboxes">
                    @foreach ($payments as $gateway)
                        <div class="form-checkbox">
                            <input class="form-check-input" name="gateways[{{ $gateway->id }}]" type="checkbox"
                                value="{{ $gateway->id }}" id="gateways[{{ $gateway->id }}]">
                            <label class="form-check-label" for="gateways[{{ $gateway->id }}]">
                                {{ $gateway->name }}
                                <small>{{ $gateway->adapter }}</small>
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
