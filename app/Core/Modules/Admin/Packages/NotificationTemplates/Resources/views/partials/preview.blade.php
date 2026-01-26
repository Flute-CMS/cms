@props([
    'template' => null,
])

@php
    $buttons = [];
    $components = $template?->getComponents();
    if ($components) {
        foreach ($components as $comp) {
            if (($comp['type'] ?? '') === 'actions' && isset($comp['buttons'])) {
                $buttons = $comp['buttons'];
                break;
            }
        }
    }
@endphp

@push('scripts')
    @at('Core/Modules/Admin/Packages/NotificationTemplates/Resources/assets/js/notifications.js')
@endpush

<div class="notification-preview-wrapper">
    <div class="notification-preview__label">
        <x-icon path="ph.bold.eye-bold" />
        {{ __('admin-notifications.preview.title') }}
    </div>

    <div class="notification-preview-item" data-preview
        data-default-title="{{ __('admin-notifications.fields.title') }}"
        data-default-content="{{ __('admin-notifications.fields.content') }}"
        data-default-button="{{ __('admin-notifications.templates.button') }}">
        <div class="notification-preview-item__icon" data-preview-icon>
            <x-icon :path="$template->icon ?: 'ph.bold.bell-bold'" />
        </div>

        <div class="notification-preview-item__content">
            <div class="notification-preview-item__header">
                <h6 class="notification-preview-item__title" data-preview-title>{{ $template->title ?? '' }}</h6>
                <span class="notification-preview-item__time">{{ __('admin-notifications.preview.just_now') }}</span>
            </div>

            <div class="notification-preview-item__text" data-preview-content>{{ $template->content ?? '' }}</div>

            @if (!empty($buttons))
                <div class="notification-preview-item__buttons" data-preview-buttons>
                    @foreach ($buttons as $button)
                        <span class="notification-preview-item__btn">{{ $button['label'] ?? '' }}</span>
                    @endforeach
                </div>
            @else
                <div class="notification-preview-item__buttons" data-preview-buttons style="display: none;"></div>
            @endif
        </div>
    </div>
</div>
