@if ($user)
    @if (!empty($user->pendingEmail))
        <x-fields.small class="text-warning d-block mb-1">
            {{ __('admin-users.fields.email.pending', ['email' => $user->pendingEmail]) }}
        </x-fields.small>
    @endif

    @if (config('auth.registration.confirm_email'))
        <x-fields.small class="d-block mb-1">
            {{ __('admin-users.fields.email.confirm_enabled') }}
        </x-fields.small>
    @endif

    @if (!empty($user->pendingEmail) || (!$user->verified && $user->email))
        <div class="d-flex gap-2 flex-wrap mt-1">
            @if (!empty($user->pendingEmail))
                <x-button type="outline-primary" size="tiny"
                    yoyo:post="applyPendingEmail" yoyo:on="click" withLoading
                    icon="ph.bold.check-bold"
                    data-tooltip="{{ __('admin-users.buttons.apply_pending_email_hint') }}">
                    {{ __('admin-users.buttons.apply_pending_email') }}
                </x-button>
                <x-button type="outline-error" size="tiny"
                    yoyo:post="cancelPendingEmail" yoyo:on="click" withLoading
                    icon="ph.bold.x-bold">
                    {{ __('admin-users.buttons.cancel_pending_email') }}
                </x-button>
            @endif

            @if (!$user->verified && $user->email)
                <x-button type="outline-accent" size="tiny"
                    yoyo:post="verifyUserEmail" yoyo:on="click" withLoading
                    icon="ph.bold.seal-check-bold"
                    data-tooltip="{{ __('admin-users.buttons.verify_email_hint') }}">
                    {{ __('admin-users.buttons.verify_email') }}
                </x-button>
                <x-button type="outline-primary" size="tiny"
                    yoyo:post="sendVerificationEmail" yoyo:on="click" withLoading
                    icon="ph.bold.envelope-bold">
                    {{ __('admin-users.buttons.send_verification') }}
                </x-button>
            @endif
        </div>
    @endif
@endif
