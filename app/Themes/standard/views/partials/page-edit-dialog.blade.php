<aside class="modal right_sidebar" id="page-edit-dialog" aria-hidden="true" aria-labelledby="page-edit-dialog-title"
    role="dialog" data-a11y-dialog>
    <div class="right_sidebar__overlay" tabindex="-1" data-a11y-dialog-hide></div>
    <div class="right_sidebar__container" id="page-edit-dialog-container" role="dialog" aria-modal="true"
        data-a11y-dialog-ignore-focus-trap>
        <header class="right_sidebar__header">
            <h5 class="right_sidebar__title" id="modal-1-title">
                @t('def.widget_settings')
            </h5>
            <button class="right_sidebar__close" aria-label="Close modal"
                data-a11y-dialog-hide="page-edit-dialog"></button>
        </header>

        <div class="right_sidebar__content page-edit-dialog-content" id="page-edit-dialog-content"></div>

        <div class="right_sidebar__footer w-100">
            <x-button type="primary" class="w-100" id="widget-settings-save-btn"
                hx-include="#page-edit-dialog-content form"
                hx-swap="none">
                @t('def.save')
            </x-button>
        </div>
    </div>
</aside>