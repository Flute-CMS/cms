<form class="profile-admin-modal" hx-post="{{ url('api/profile/' . $user->id . '/remove-balance') }}"
    hx-swap="none" hx-on::after-request="if(event.detail.successful) { closeModal('profile-remove-balance-modal'); setTimeout(() => location.reload(), 300); }">
    <div class="profile-admin-modal__info">
        <div class="profile-admin-modal__user">
            <img src="{{ url($user->avatar) }}" alt="{{ $user->name }}" class="profile-admin-modal__avatar">
            <div class="profile-admin-modal__user-details">
                <span class="profile-admin-modal__name">{{ $user->name }}</span>
                <span class="profile-admin-modal__balance" data-profile-balance>
                    @t('profile.admin_actions.current_balance'): <strong>{{ number_format($user->balance, 2) }}</strong>
                </span>
            </div>
        </div>
    </div>

    <div class="profile-admin-modal__field">
        <label for="remove-balance-amount" class="profile-admin-modal__label">
            @t('profile.admin_actions.amount')
        </label>
        <x-fields.input type="number" name="amount" id="remove-balance-amount" step="0.01" min="0.01"
            max="{{ $user->balance }}" placeholder="{{ __('profile.admin_actions.amount_placeholder') }}" required />
        <span class="profile-admin-modal__hint">
            @t('profile.admin_actions.max_amount', ['amount' => number_format($user->balance, 2)])
        </span>
    </div>

    <div class="profile-admin-modal__actions">
        <x-button type="outline-primary" data-a11y-dialog-hide>
            @t('def.cancel')
        </x-button>
        <x-button type="error" submit withLoading>
            <x-icon path="ph.regular.minus" />
            @t('profile.admin_actions.remove_balance')
        </x-button>
    </div>
</form>

