@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', [
        'name' => __('admin.users.edit_title', [
            'name' => $user->name,
        ]),
    ]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/users.scss')
    <link rel="stylesheet" href="@asset('assets/css/libs/tables.css')" />
@endpush

@push('content')
    <div class="admin-header d-flex align-items-center">
        <a href="{{ url('admin/users/list') }}" class="back_btn">
            <i class="ph ph-caret-left"></i>
        </a>
        <div>
            <h2>@t('admin.users.edit_title', [
                'name' => $user->name,
            ])</h2>
            <p>@t('admin.users.edit_description')</p>
        </div>
    </div>

    <div class="row gx-4 gy-4">
        <div class="col-md-6">
            <div class="card-user">
                <h3 class="card-user-header">
                    @t('admin.users.edit')
                </h3>
                <div class="card-user-content">
                    <form id="edit" data-userid="{{ $user->id }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-9">
                                <div class="form-group mb-3">
                                    <label class="form-label" for="nameInput">@t('admin.users.name')</label>
                                    <input type="text" class="form-control" id="nameInput" name="name"
                                        value="{{ $user->name }}" required="">
                                </div>

                                <div class="form-group mb-3">
                                    <label class="form-label" for="emailInput">@t('admin.users.email')</label>
                                    <input type="email" class="form-control" id="emailInput" name="email"
                                        value="{{ $user->email }}">
                                </div>
                            </div>

                            <div class="col-md-3 text-center">
                                <img src="{{ url($user->avatar) }}" alt="{{ $user->name }}" class="avatar mb-3">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label class="form-label" for="balanceInput">@t('admin.users.balance')</label>
                                    <input type="number" step="0.1" class="form-control" id="balanceInput"
                                        name="balance" value="{{ $user->balance }}">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label class="form-label" for="loginInput">@t('admin.users.login')</label>
                                    <input type="text" class="form-control" id="loginInput" name="login"
                                        value="{{ $user->login }}">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label class="form-label" for="uriInput">@t('admin.users.uri')</label>
                                    <input type="text" class="form-control" id="uriInput" name="uri"
                                        value="{{ $user->uri }}">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label class="form-label" for="rolesInput">@t('admin.users.roles')</label>
                                    <div class="chips_container">
                                        <div class="chip-input">
                                            <div class="chips">
                                            </div>
                                        </div>
                                        <div class="dialog hidden"></div>
                                    </div>
                                </div>
                            </div>
                            @stack('admin-user::details')
                        </div>

                        <button type="submit" class="btn primary size-s">
                            @t('def.save')
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-user">
                <h3 class="card-user-header">
                    @t('admin.users.information')
                </h3>

                <div class="card-user-content">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group mb-3">
                                <label class="form-label">@t('admin.users.created_at')</label>
                                <input disabled type="text" class="form-control"
                                    value="{{ $user->created_at->format('Y-m-d H:i:s') }}">
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label">@t('admin.users.verified')</label>
                                <input disabled type="text" class="form-control"
                                    value="{{ __($user->verified ? 'admin.users.verf' : 'admin.users.not_verf') }}">
                            </div>
                            <div class="form-group empty mb-3">
                                <label class="form-label">@t('admin.users.hidden')</label>
                                <input disabled type="text" class="form-control"
                                    value="{{ __($user->hidden ? 'admin.users.hid' : 'admin.users.not_hid') }}">
                            </div>

                            @stack('admin-user::info')
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (sizeof($user->actionLogs) > 0)
            <div class="col-md-12">
                <div class="card-user">
                    <h3 class="card-user-header">
                        @t('admin.users.logs')
                    </h3>
                    <div class="card-user-content">
                        <table class="dataTable table">
                            <thead>
                                <tr role="row">
                                    <th>@t('admin.users.action')</th>
                                    <th>@t('admin.users.action_details')</th>
                                    <th>@t('admin.users.action_date')</th>
                                    <th></th>
                                </tr>
                            </thead>
                            @foreach ($user->actionLogs as $key => $log)
                                <tbody>
                                    <tr role="row">
                                        <td>{{ __('action.' . $log->action) }}</td>
                                        <td>{{ $log->details ?? '-' }}</td>
                                        <td>{{ $log->actionDate->format('Y-m-d H:i:s') }}</td>
                                        <td>
                                            @if ($log->url)
                                                <a class="goto_btn" href="{{ $log->url }}">@t('def.goto')</a>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
        @endif

        @if (sizeof($user->socialNetworks) > 0)
            <div class="col-md-12">
                <div class="card-user">
                    <h3 class="card-user-header">
                        @t('admin.users.social')
                    </h3>
                    <table class="dataTable table">
                        {{-- <thead>
                            <tr role="row">
                                <th>@t('admin.users.action')</th>
                                <th>@t('admin.users.action_details')</th>
                                <th>@t('admin.users.action_date')</th>
                                <th></th>
                            </tr>
                        </thead> --}}
                        @foreach ($user->socialNetworks as $network)
                            <tbody>
                                <tr role="row">
                                    <td class="social_name_icon">
                                        {!! $network->socialNetwork->icon !!}
                                        <div>{{ $network->socialNetwork->key }}</div>
                                    </td>
                                    <td>
                                        <a href="{{ $network->url }}" target="_blank">{{ $network->name }}</a>
                                    </td>

                                    <td class="show_icon">
                                        @if ((bool) $network->hidden === true)
                                            <div data-tooltip="@t('admin.users.hid')">
                                                <i class="ph ph-eye-slash"></i>
                                            </div>
                                        @else
                                            <div data-tooltip="@t('admin.users.not_hid')">
                                                <i class="ph ph-eye"></i>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        @endforeach
                    </table>
                </div>
            </div>
        @endif

        @if (sizeof($user->invoices) > 0)
            <div class="col-md-12">
                <div class="card-user">
                    <h3 class="card-user-header">
                        @t('admin.users.invoices.title')
                    </h3>
                    <table class="dataTable table">
                        <thead>
                            <tr role="row">
                                <th>ID</th>
                                <th>@t('admin.users.invoices.gateway')</th>
                                <th>@t('admin.users.invoices.amount')</th>
                                <th>@t('admin.users.invoices.promo')</th>
                                <th>@t('admin.users.invoices.paid_at')</th>
                            </tr>
                        </thead>
                        @foreach ($user->invoices as $invoice)
                            <tbody>
                                <tr role="row">
                                    <td>{{ $invoice->transactionId }}</td>
                                    <td>{{ $invoice->gateway }}</td>
                                    <td>{{ $invoice->originalAmount . config('lk.currency_view') }}</td>
                                    <td>{{ $invoice->promoCode ? $invoice->promoCode->code : '-' }}</td>
                                    <td>{{ $invoice->paidAt->format('Y-m-d H:i:s') }}</td>
                                </tr>
                            </tbody>
                        @endforeach
                    </table>
                </div>
            </div>
        @endif

        @if (sizeof($user->userDevices) > 0)
            <div class="col-md-12">
                <div class="card-user">
                    <h3 class="card-user-header">
                        @t('admin.users.devices')
                    </h3>
                    <table class="dataTable table">
                        <thead>
                            <tr role="row">
                                <th>@t('admin.users.device')</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        @foreach ($user->userDevices as $devices)
                            <tbody>
                                <tr role="row">
                                    <td>{{ $devices->deviceDetails }}</td>
                                    <td>{{ $devices->ip }}</td>
                                </tr>
                            </tbody>
                        @endforeach
                    </table>
                </div>
            </div>
        @endif

        @stack('admin-user::container')
    </div>
@endpush

@push('footer')
    <script>
        const roles = {!! json_encode($roles) !!};
        const user_roles = {!! json_encode($user->roles->toArray()) !!};
    </script>

    @at('Core/Admin/Http/Views/assets/js/pages/users/edit.js')
@endpush
