@push('scripts')
    @at('Core/Modules/Admin/Packages/NotificationTemplates/Resources/assets/js/broadcast-preview.js')
@endpush

<div class="notification-preview-wrapper">
    <div class="notification-preview__label">
        <x-icon path="ph.bold.eye-bold" />
        {{ __('admin-notifications.preview.title') }}
    </div>

    <div class="notification-preview-item" data-broadcast-preview
        data-default-title="{{ __('admin-notifications.broadcast.notification_title') }}"
        data-default-content="{{ __('admin-notifications.broadcast.notification_content') }}">
        <div class="notification-preview-item__icon" data-broadcast-preview-icon>
            <x-icon path="ph.bold.bell-bold" />
        </div>

        <div class="notification-preview-item__content">
            <div class="notification-preview-item__header">
                <h6 class="notification-preview-item__title" data-broadcast-preview-title>
                    {{ __('admin-notifications.broadcast.notification_title') }}
                </h6>
                <span class="notification-preview-item__time">{{ __('admin-notifications.preview.just_now') }}</span>
            </div>

            <div class="notification-preview-item__text" data-broadcast-preview-content>
                {{ __('admin-notifications.broadcast.notification_content') }}
            </div>

            <div class="notification-preview-item__url" data-broadcast-preview-url style="display: none;">
                <x-icon path="ph.bold.link-bold" />
                <span data-broadcast-preview-url-text></span>
            </div>
        </div>
    </div>
</div>
