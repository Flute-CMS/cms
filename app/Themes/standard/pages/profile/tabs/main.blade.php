@push('header')
    @at(tt('assets/styles/pages/profile_tabs/main.scss'))
@endpush

@push('profile_body')
    <div class="row gx-3 gy-3 main-tab-container @if (!user()->canEditUser($user)) justify-content-center @endif">
        <div class="col-md-4">
            <div class="main-tab-container-item">
                <i class="ph ph-identification-card"></i>
                <div>
                    <p>ID</p>
                    <h3>{{ $user->id }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="main-tab-container-item">
                <i class="ph ph-calendar"></i>
                <div>
                    <p>@t('admin.users.created_at')</p>
                    <h3>{{ $user->created_at->format(default_date_format()) }}</h3>
                </div>
            </div>
        </div>
        @if (user()->canEditUser($user) || $user->id === user()->id)
            <div class="col-md-4">
                <div class="main-tab-container-item">
                    <i class="ph ph-at"></i>
                    <div>
                        <p>E-Mail</p>
                        <h3>{{ $user->email ?: '-' }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="main-tab-container-item">
                    <i class="ph ph-user-circle"></i>
                    <div>
                        <p>@t('admin.users.login')</p>
                        <h3>{{ $user->login ?: '-' }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="main-tab-container-item">
                    <i class="ph ph-check-circle"></i>
                    <div>
                        <p>@t('admin.users.verified')</p>
                        <h3>{{ __($user->verified ? 'admin.users.verf' : 'admin.users.not_verf') }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="main-tab-container-item">
                    <i class="ph ph-eye"></i>
                    <div>
                        <p>@t('admin.users.hidden')</p>
                        <h3>{{ __($user->hidden ? 'admin.users.hid' : 'admin.users.not_hid') }}</h3>
                    </div>
                </div>
            </div>

            @if (user()->canEditUser($user))
                <div class="col-md-4">
                    <div class="main-tab-container-item">
                        <i class="ph ph-lock-key"></i>
                        <div>
                            <p>@t('profile.main.bans') @if (sizeof($user->blocksReceived) > 0)
                                    <button data-lookblocks class="button">@t('def.view_all')</button>
                                @endif
                            </p>
                            <h3>{{ sizeof($user->blocksReceived) }}</h3>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>

    @if (user()->canEditUser($user) && sizeof($user->blocksReceived) > 0)
        <div class="bans-modal">
            <div class="bans-modal-card">
                <div class="bans-modal-card-header">
                    <h3 class="bans-modal-card-header-name">@t('profile.main.all_bans')</h3>
                    <div class="bans-modal-card-header-close">
                        <i class="ph ph-x"></i>
                    </div>
                </div>
                <div class="bans-modal-card-content">
                    {{-- FOR DESC --}}
                    @foreach(array_reverse($user->blocksReceived->toArray()) as $block)
                        <div class="bans-content-card">
                            <a href="{{ url('profile/'.$block->blockedBy->id) }}">{{ $block->blockedBy->name }}</a>
                            <p>{{ $block->reason }}</p>
                            <p>{{ $block->blockedFrom->format(default_date_format()) }} <i class="ph ph-arrow-right"></i> {{ $block->blockedUntil === null ? __('admin.users.times.0') : $block->blockedUntil->format(default_date_format()) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
@endpush

@push('footer')
    @at(tt('assets/js/pages/profile_tabs/main.js'))
@endpush