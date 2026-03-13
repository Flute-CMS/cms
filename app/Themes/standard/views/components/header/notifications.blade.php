<li class="navbar__notifications-wrapper">
    <button class="navbar__notifications" data-notification-toggle
        data-tooltip="{{ __('def.notifications') }}" aria-label="{{ __('def.notifications') }}"
        aria-expanded="false" aria-haspopup="true">
        <x-icon path="ph.regular.bell" aria-hidden="true" />

        <span class="navbar__notifications-dot" id="notification-dot"
            hx-get="{{ url('api/notifications/has-unread') }}"
            hx-trigger="load, every 15s, notificationsUpdated from:body"
            hx-target="this"
            hx-swap="none"
            data-disable-loading-states
            data-noprogress
            data-popup-enabled="{{ config('app.notifications_popup_enabled', true) ? 'true' : 'false' }}"
            hx-on::after-request="
                try {
                    const data = JSON.parse(event.detail.xhr.responseText);
                    this.classList.toggle('active', data.hasUnread === true);
                    document.body.dispatchEvent(new CustomEvent('notificationPoll', {
                        detail: { hasUnread: data.hasUnread, newestId: data.newestId }
                    }));
                } catch(e) {}
            "
            class="{{ notification()->hasUnread() ? 'active' : '' }}"
            role="status"
            aria-label="{{ __('def.unread_notifications') }}">
        </span>
    </button>
    <div id="error-hide-div" style="display: none;"></div>

    <div class="notification-dropdown" data-notification-dropdown aria-hidden="true" hx-boost="false">
        <div class="notification-dropdown__header">
            <h5 class="notification-dropdown__title">@t('def.notifications')</h5>
            <div class="notification-dropdown__actions">
                <button class="notification-dropdown__action" data-mark-all-read
                    data-tooltip="@t('def.mark_all_read')">
                    <x-icon path="ph.regular.checks" />
                </button>
                <button class="notification-dropdown__action notification-dropdown__action--danger" data-clear-all
                    data-tooltip="@t('def.clear_all')">
                    <x-icon path="ph.regular.trash" />
                </button>
            </div>
        </div>

        <div class="notification-dropdown__tabs">
            <button class="notification-dropdown__tab active" data-notification-tab="unread">
                @t('def.not_read')
                <span class="notification-dropdown__badge" data-notification-count-unread>{{ notification()->countUnread() }}</span>
            </button>
            <button class="notification-dropdown__tab" data-notification-tab="all">
                @t('def.all')
                <span class="notification-dropdown__badge" data-notification-count-all>{{ notification()->countAll() }}</span>
            </button>
        </div>

        <div class="notification-dropdown__content" data-notification-content data-empty-text="@t('def.no_notifications')">
            <div class="notification-dropdown__list" data-notification-list="unread"
                hx-get="{{ url('sidebar/notifications/unread') }}" hx-trigger="load" hx-swap="innerHTML"
                hx-target="this">
                @for ($i = 0; $i < 3; $i++)
                    <div class="notification-dropdown__skeleton">
                        <div class="skeleton notification-dropdown__skeleton-avatar"></div>
                        <div class="notification-dropdown__skeleton-body">
                            <div class="skeleton notification-dropdown__skeleton-title"></div>
                            <div class="skeleton notification-dropdown__skeleton-text"></div>
                        </div>
                    </div>
                @endfor
            </div>
            <div class="notification-dropdown__list" data-notification-list="all" style="display: none;"
                hx-get="{{ url('sidebar/notifications/all') }}" hx-trigger="revealed" hx-swap="innerHTML"
                hx-target="this">
                @for ($i = 0; $i < 3; $i++)
                    <div class="notification-dropdown__skeleton">
                        <div class="skeleton notification-dropdown__skeleton-avatar"></div>
                        <div class="notification-dropdown__skeleton-body">
                            <div class="skeleton notification-dropdown__skeleton-title"></div>
                            <div class="skeleton notification-dropdown__skeleton-text"></div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    </div>
</li>
