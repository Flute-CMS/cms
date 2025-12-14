<x-button class="page-edit-button" id="page-edit-button" size="medium" data-dropdown-open="__dropdown_page-edit"
    type="primary">
    <x-icon path="ph.regular.magic-wand" />{{ __('def.edit_page') }}
</x-button>

<div class="page-edit-dropdown" data-dropdown="__dropdown_page-edit">
    <x-button size="medium" type="outline-primary" id="page-change-button">
        <x-icon path="ph.regular.magic-wand" />
        {{ __('def.edit_page') }}
    </x-button>
    <x-button size="medium" type="outline-primary" id="page-change-seo" data-modal-open="page-seo-dialog"
        hx-get="{{ route('pages.seo') }}" hx-vals="js:{'route': window.location.pathname}" hx-trigger="click"
        hx-target="#page-seo-dialog-content" hx-swap="morph:outerHTML">
        <x-icon path="ph.regular.pencil" />
        {{ __('page.seo.edit_seo') }}
    </x-button>
    <x-button class="colors" size="medium" type="outline-primary" id="page-change-colors">
        <x-icon path="ph.regular.palette" />
        {{ __('def.edit_colors') }}
    </x-button>
</div>
