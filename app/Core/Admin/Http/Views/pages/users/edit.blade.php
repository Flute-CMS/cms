@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', [
        'name' => __('admin.users.edit_title', [
            'name' => $user->name,
        ]),
    ]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/users.scss')
    @at('Core/Admin/Http/Views/assets/styles/components/_tables.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <a class="back-btn" href="{{ url('admin/users/list') }}">
                <i class="ph ph-arrow-left ignore"></i>
                @t('def.back')
            </a>
            <h2>@t('admin.users.edit_title', [
                'name' => htmlentities($user->name),
            ])</h2>
            <p>@t('admin.users.edit_description')</p>
        </div>
        <div class="user-actions">
            @if ($user->id !== user()->id)
                @if ($user->isBlocked())
                    <button class="unblock" data-useraction="unblock" data-tooltip="@t('admin.users.unblock')">
                        <i class="ph ph-lock-simple-open"></i>
                    </button>
                @else
                    <button class="block" data-useraction="block" data-tooltip="@t('admin.users.block')">
                        <i class="ph ph-lock"></i>
                    </button>
                @endif
            @endif
            <button class="give_money" data-useraction="give_money" data-tooltip="@t('admin.users.give_money')">
                <i class="ph ph-hand-coins"></i>
            </button>
            <button class="take_money" data-useraction="take_money" data-tooltip="@t('admin.users.take_money')"
                data-tooltip-conf="left">
                <i class="ph ph-arrow-u-down-left"></i>
            </button>
            <a href="{{ url('profile/'.$user->getUrl()) }}" class="btn btn--with-icon size-s outline ignore" target="_blank">
                @t('def.goto') 
                <span class="btn__icon arrow"><i class="ph ph-arrow-right"></i></span>
            </a>
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
                                <img src="{{ url($user->avatar) }}" alt="{{ $user->name }}" class="avatar mb-3" data-page-icon>
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
                                            <div class="chips" id="user_chils">
                                            </div>
                                        </div>
                                        <div class="dialog hidden" id="user_dialog"></div>
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
                                    value="{{ $user->created_at->format(default_date_format()) }}">
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label">@t('admin.users.verified')</label>
                                <input disabled type="text"
                                    class="form-control @if ($user->verified) success-control @else error-control @endif"
                                    value="{{ __($user->verified ? 'admin.users.verf' : 'admin.users.not_verf') }}">
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label">@t('admin.users.hidden')</label>
                                <input disabled type="text" class="form-control"
                                    value="{{ __($user->hidden ? 'admin.users.hid' : 'admin.users.not_hid') }}">
                            </div>

                            @if (sizeof($user->socialNetworks) > 0)
                                <div class="form-group mb-3">
                                    <label class="form-label">@t('admin.users.socials')</label>
                                    <div class="admin-user-socials">
                                        @foreach ($user->socialNetworks as $network)
                                        <a href="{{ $network->url }}" target="_blank">
                                            <div data-tooltip="{{ $network->socialNetwork->key }}"
                                                data-tooltip-conf="top">
                                                {!! $network->socialNetwork->icon !!}
                                            </div>
                                        </a>
                                    @endforeach
                                    </div>
                                    @stack('admin::profile_socials')
                                </div>
                            @endif

                            <div class="form-group @if (!$user->isBlocked()) empty @endif mb-3">
                                <label class="form-label">@t('admin.users.status')</label>
                                <input disabled type="text"
                                    class="form-control @if ($user->isBlocked()) error-control @endif"
                                    value="{{ __($user->isBlocked() ? 'admin.users.blocked' : 'admin.users.not_blocked') }}">
                            </div>

                            @if ($user->isBlocked())
                                <div class="form-group mb-3">
                                    <label class="form-label">@t('admin.users.blocked_by')</label>
                                    <a @if (user()->canEditUser($user)) data-tab @endif
                                        href="{{ url(user()->canEditUser($user) ? 'admin/users/edit/' . $user->getBlockInfo()['blockedBy']->id : 'profile/' . $user->getBlockInfo()['blockedBy']->id) }}"
                                        class="blocked-by-container">
                                        <img src="@at($user->getBlockInfo()['blockedBy']->avatar)" alt="" srcset="@at($user->getBlockInfo()['blockedBy']->avatar)">
                                        <p>{{ $user->getBlockInfo()['blockedBy']->name }}</p>
                                    </a>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="form-label">@t('admin.users.blocked_reason')</label>
                                    <input disabled type="text" class="form-control"
                                        value="{{ $user->getBlockInfo()['reason'] }}">
                                </div>
                                <div class="form-group mb-3">
                                    <label class="form-label">@t('admin.users.blocked_from')</label>
                                    <input disabled type="text" class="form-control"
                                        value="{{ $user->getBlockInfo()['blockedFrom']->format(default_date_format()) }}">
                                </div>
                                <div class="form-group empty mb-3">
                                    <label class="form-label">@t('admin.users.blocked_until')</label>
                                    <input disabled type="text"
                                        class="form-control @if ($user->getBlockInfo()['blockedUntil'] === null) error-control @endif"
                                        value="{{ $user->getBlockInfo()['blockedUntil'] === null ? __('admin.users.times.0') : $user->getBlockInfo()['blockedUntil']->format(default_date_format()) }}">
                                </div>
                            @endif

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
                                    <th>@t('admin.users.action_date')</th>
                                    <th>@t('admin.users.action')</th>
                                    <th>@t('admin.users.action_details')</th>
                                    <th></th>
                                </tr>
                            </thead>
                            @foreach ($user->actionLogs as $key => $log)
                                <tbody>
                                    <tr role="row">
                                        <td>{{ $log->actionDate->format(default_date_format()) }}</td>
                                        <td>{{ __('action.' . $log->action) }}</td>
                                        <td>{{ $log->details ?? '-' }}</td>
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
                                    <td>{{ $invoice->originalAmount . ' ' . $invoice->currency->code }}</td>
                                    <td>{{ $invoice->promoCode ? $invoice->promoCode->code : '-' }}</td>
                                    <td>{{ $invoice->paidAt->format(default_date_format()) }}</td>
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
    <input type="hidden" name="selected-duration" id="selected-duration" value="60">
@endpush

@push('footer')
    <script data-loadevery>
        if (typeof roles === 'undefined' && typeof selectedRoles === 'undefined') {
            var roles = [],
                selectedRoles = [];
        }

        roles = {!! json_encode($roles) !!};
        selectedRoles[{{ $user->id }}] = {!! json_encode($user->roles->toArray()) !!};
    </script>

    @at('Core/Admin/Http/Views/assets/js/pages/users/edit.js')
@endpush
