<div class="page-edit-controls">
    <button class="page-edit-control-btn" id="height-mode-toggle"
        data-tooltip-auto="{{ __('page.edit_nav.auto_height') }}"
        data-tooltip-manual="{{ __('page.edit_nav.manual_height') }}">
        <span class="icon-auto">
            <x-icon path="ph.regular.arrows-out-line-vertical" />
        </span>
        <span class="icon-manual" style="display: none;">
            <x-icon path="ph.regular.arrows-in-line-vertical" />
        </span>
    </button>
    <button class="page-edit-control-btn" id="page-edit-auto-position"
        data-tooltip="{{ __('page.edit_nav.auto_position') }}">
        <x-icon path="ph.regular.magic-wand" />
    </button>
</div> 