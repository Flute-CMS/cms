<li>
    <button hx-get="{{ url('sidebar/notifications') }}" class="navbar__notifications" hx-target="#right-sidebar-content"
        hx-swap="innerHTML transition:false" aria-expanded="false" data-disable-loading-states
        data-tooltip="{{ __('def.notifications') }}" aria-label="{{ __('def.notifications') }}">
        <x-icon path="ph.regular.bell" aria-hidden="true" />

        <span class="navbar__notifications-indicator" id="notification-count" hx-target-4x="#error-hide-div"
            hx-target-error="#error-hide-div" hx-get="{{ url('api/notifications/count-unread') }}"
            hx-trigger="load, every 10s" hx-target="#notification-count" role="status"
            aria-label="{{ __('def.unread_notifications') }}" hx-swap="innerHTML" data-disable-loading-states
            data-noprogress>
            {{ notification()->countUnread() > 0 ? notification()->countUnread() : '' }}
        </span>
    </button>
    <div id="error-hide-div" style="display: none;"></div>
</li>