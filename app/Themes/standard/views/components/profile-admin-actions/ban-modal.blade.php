<form class="profile-admin-modal" hx-post="{{ url('api/profile/' . $user->id . '/ban') }}" hx-swap="none"
    hx-on::after-request="if(event.detail.successful) { closeModal('profile-ban-modal'); setTimeout(() => location.reload(), 300); }">
    <div class="profile-admin-modal__info">
        <div class="profile-admin-modal__user">
            <img src="{{ url($user->avatar) }}" alt="{{ $user->name }}" class="profile-admin-modal__avatar">
            <div class="profile-admin-modal__user-details">
                <span class="profile-admin-modal__name">{{ $user->name }}</span>
            </div>
        </div>
    </div>

    <div class="profile-admin-modal__field">
        <label for="ban-reason" class="profile-admin-modal__label">
            @t('profile.admin_actions.ban_reason')
        </label>
        <textarea name="reason" id="ban-reason" class="input__field" rows="3"
            placeholder="{{ __('profile.admin_actions.ban_reason_placeholder') }}" required></textarea>
    </div>

    <div class="profile-admin-modal__field">
        <label for="ban-until" class="profile-admin-modal__label">
            @t('profile.admin_actions.ban_until')
        </label>
        <x-fields.input type="date" name="blocked_until" id="ban-until" />
        <span class="profile-admin-modal__hint">
            @t('profile.admin_actions.ban_until_hint')
        </span>
    </div>

    <div class="profile-admin-modal__actions">
        <x-button type="outline-primary" data-a11y-dialog-hide>
            @t('def.cancel')
        </x-button>
        <x-button type="error" submit withLoading>
            <x-icon path="ph.regular.prohibit" />
            @t('profile.admin_actions.ban_user')
        </x-button>
    </div>
</form>

