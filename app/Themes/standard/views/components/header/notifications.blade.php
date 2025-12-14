<li>
    <button hx-get="{{ url('sidebar/notifications') }}" class="navbar__notifications" hx-target="#right-sidebar-content"
        hx-swap="innerHTML transition:false" aria-expanded="false" data-disable-loading-states
        data-tooltip="{{ __('def.notifications') }}" aria-label="{{ __('def.notifications') }}">
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
</li>
