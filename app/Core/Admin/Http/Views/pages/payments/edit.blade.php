@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', [
        'name' => __('admin.payments.edit_title', [
            'name' => $gateway->name,
        ]),
    ]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/payments.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <a class="back-btn" href="{{ url('admin/payments/list') }}">
                <i class="ph ph-arrow-left ignore"></i>
                @t('def.back')
            </a>
            <h2>@t('admin.payments.edit_title', [
                'name' => $gateway->name,
            ])</h2>
            <p>@t('admin.payments.edit_description')</p>
        </div>
        <div>
            <button data-deleteaction="{{ $gateway->id }}" data-deletepath="payments" class="btn size-s error outline">
                @t('def.delete')
            </button>
        </div>
    </div>

    <form id="gatewayEdit">
        @csrf
        <input type="hidden" name="id" value="{{ $gateway->id }}">
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="name">
                    @t('admin.payments.gateway_label')
                </label>
            </div>
            <div class="col-sm-9">
                <input name="name" id="name" placeholder="@t('admin.payments.gateway_label')" type="text" class="form-control"
                    value="{{ $gateway->name }}" required>
            </div>
        </div>
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="adapter">
                    @t('admin.payments.adapter')
                </label>
                <small>@t('admin.payments.adapter_description')</small>
            </div>
            <div class="col-sm-9">
                <select name="adapter" id="adapter" class="form-control">
                    @foreach ($drivers as $key => $item)
                        <option value="{{ $key }}" @if ($gateway->adapter === $key) selected @endif>
                            {{ $item['name'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="position-relative row form-group align-items-start">
            <div class="col-sm-3 col-form-label required">
                <label for="addititional">
                    @t('admin.payments.params')
                </label>
            </div>
            <div class="col-sm-9 parametersEdit" id="paymentsParametersContainer">
                @foreach ($additional as $key => $val)
                    <div class="param-group" id="param-group-{{ $key }}">
                        <input type="text" name="paramNames[]" class="form-control" placeholder="Key" required=""
                            value="{{ $key }}">
                        <input type="text" name="paramValues[]" class="form-control" placeholder="Value" required=""
                            value="{{ $val }}">
                        <button type="button" class="removeParam btn size-s error"
                            data-id="{{ $key }}">@t('def.delete')</button>
                    </div>
                @endforeach
            </div>
            <div class="col-sm-9 offset-sm-3">
                <button type="button" id="addParam" class="btn size-s outline">@t('def.add')</button>
            </div>
        </div>

        <!-- Readonly inputs for URLs -->
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="handleUrl">Handle URL</label>
            </div>
            <div class="col-sm-9" data-tooltip="@t('def.copy')" data-tooltip-conf="top">
                <input id="handleUrl" type="text" class="form-control" readonly
                    value="{{ url('/api/lk/handle/' . $gateway->adapter) }}" data-copy="{{ url('/api/lk/handle/' . $gateway->adapter) }}">
            </div>
        </div>
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="successUrl">Success URL</label>
            </div>
            <div class="col-sm-9" data-tooltip="@t('def.copy')" data-tooltip-conf="top">
                <input id="successUrl" type="text" class="form-control" readonly 
                    value="{{ url('/lk/success') }}" data-copy="{{ url('/lk/success') }}">
            </div>
        </div>
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="failUrl">Fail URL</label>
            </div>
            <div class="col-sm-9" data-tooltip="@t('def.copy')" data-tooltip-conf="top">
                <input id="failUrl" type="text" class="form-control" readonly 
                    value="{{ url('/lk/fail') }}" data-copy="{{ url('/lk/fail') }}">
            </div>
        </div>
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="enabled">
                    @t('admin.payments.enabled')</label>
                <small>@t('admin.payments.enabled_description')</small>
            </div>
            <div class="col-sm-9">
                <input name="enabled" role="switch" id="enabled" type="checkbox" class="form-check-input"
                    @if ($gateway->enabled) checked @endif>
                <label for="enabled"></label>
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
    <script type="text/javascript">
        var drivers = {!! json_encode($drivers) !!};
    </script>

    @at('Core/Admin/Http/Views/assets/js/pages/payments/add.js')
@endpush
