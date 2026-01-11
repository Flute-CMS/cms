<div class="page-edit-fab" id="page-edit-fab">
    <button class="page-edit-fab__trigger" id="page-edit-trigger" data-tooltip="{{ __('def.edit_page') }}" data-tooltip-pos="left">
        <x-icon path="ph.regular.pencil-simple" />
    </button>

    <div class="page-edit-fab__ring" id="page-edit-menu">
        <button class="page-edit-fab__item" id="page-open-editor" 
            data-tooltip="{{ __('page-edit.editor_title') }}" data-tooltip-pos="left"
            style="--i: 0;">
            <x-icon path="ph.regular.sliders" />
        </button>

        <button class="page-edit-fab__item" id="page-change-button" 
            data-tooltip="{{ __('page-edit.edit_widgets') }}" data-tooltip-pos="left"
            style="--i: 1;">
            <x-icon path="ph.regular.squares-four" />
        </button>

        <button class="page-edit-fab__item" id="page-change-seo" data-modal-open="page-seo-dialog"
            hx-get="{{ route('pages.seo') }}" hx-vals="js:{'route': window.location.pathname}" hx-trigger="click"
            hx-target="#page-seo-dialog-content" hx-swap="morph:outerHTML" 
            data-tooltip="{{ __('page.seo.edit_seo') }}" data-tooltip-pos="left"
            style="--i: 2;">
            <x-icon path="ph.regular.magnifying-glass" />
        </button>
    </div>

    <div class="page-edit-fab__backdrop" id="page-edit-backdrop"></div>
</div>
