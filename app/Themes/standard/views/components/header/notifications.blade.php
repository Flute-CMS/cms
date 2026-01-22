<li class="navbar__notifications-wrapper">
    <button class="navbar__notifications" data-notification-toggle
        data-tooltip="{{ __('def.notifications') }}" aria-label="{{ __('def.notifications') }}"
        aria-expanded="false" aria-haspopup="true">
        <x-icon path="ph.regular.bell" aria-hidden="true" />

        <span class="navbar__notifications-indicator" id="notification-count" hx-target-4x="#error-hide-div"
            hx-target-error="#error-hide-div" hx-get="{{ url('api/notifications/count-unread') }}"
            hx-trigger="load, every 10s, refresh" hx-target="#notification-count" hx-swap="innerHTML" role="status"
            aria-label="{{ __('def.unread_notifications') }}" data-disable-loading-states data-noprogress
            hx-on="htmx:afterRequest: let response = (event.detail && event.detail.xhr && event.detail.xhr.responseText) ? event.detail.xhr.responseText.trim() : ''; let count = parseInt(response) || 0; this.style.display = count > 0 ? 'inline-block' : 'none';"
            style="{{ notification()->countUnread() > 0 ? 'display: inline-block;' : 'display: none;' }}">
        </span>
    </button>
    <div id="error-hide-div" style="display: none;"></div>

    <div class="notification-dropdown" data-notification-dropdown aria-hidden="true" hx-boost="false">
        <div class="notification-dropdown__header">
            <h5 class="notification-dropdown__title">@t('def.notifications')</h5>
            <button class="notification-dropdown__mark-read" data-mark-all-read
                data-tooltip="@t('def.mark_all_read')">
                <x-icon path="ph.regular.checks" />
            </button>
        </div>

        <div class="notification-dropdown__tabs">
            <button class="notification-dropdown__tab active" data-notification-tab="all">
                @t('def.all')
                <span class="notification-dropdown__badge" data-notification-count-all>{{ notification()->countAll() }}</span>
            </button>
            <button class="notification-dropdown__tab" data-notification-tab="unread">
                @t('def.not_read')
                <span class="notification-dropdown__badge" data-notification-count-unread>{{ notification()->countUnread() }}</span>
            </button>
        </div>

        <div class="notification-dropdown__content" data-notification-content>
            <div class="notification-dropdown__list" data-notification-list="all"
                hx-get="{{ url('sidebar/notifications/all') }}" hx-trigger="load once" hx-swap="innerHTML"
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
            <div class="notification-dropdown__list" data-notification-list="unread" style="display: none;"
                hx-get="{{ url('sidebar/notifications/unread') }}" hx-trigger="revealed once" hx-swap="innerHTML"
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
