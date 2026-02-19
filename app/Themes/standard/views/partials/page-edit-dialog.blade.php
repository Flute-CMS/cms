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

{{-- Excluded Paths editor template (used by JS for global widgets) --}}
<template id="pe-excluded-paths-tpl">
    <div class="pe-excluded-paths">
        <div class="pe-excluded-paths__header">
            <h4 class="pe-excluded-paths__title">@t('page-edit.excluded_paths')</h4>
            <p class="pe-excluded-paths__desc">@t('page-edit.excluded_paths_desc')</p>
        </div>
        <div class="pe-excluded-paths__list"></div>
        <div class="pe-excluded-paths__add">
            <input type="text" class="pe-excluded-paths__input"
                placeholder="@t('page-edit.excluded_paths_placeholder')"
                autocomplete="off" spellcheck="false" />
            <button type="button" class="pe-excluded-paths__add-btn">
                @t('page-edit.add_path')
            </button>
        </div>
        <p class="pe-excluded-paths__hint">@t('page-edit.excluded_paths_hint')</p>
    </div>

    {{-- Tag template (cloned per path) --}}
    <template class="pe-excluded-paths__tag-tpl">
        <div class="pe-excluded-paths__tag">
            <span class="pe-excluded-paths__tag-text"></span>
            <button type="button" class="pe-excluded-paths__tag-remove">&times;</button>
        </div>
    </template>
</template>