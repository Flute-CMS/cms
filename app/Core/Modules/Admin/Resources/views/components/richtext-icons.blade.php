{{-- Pre-rendered SVG icons for TipTap Rich Text Editor --}}
<template id="richtext-editor-icons">
    {{-- Formatting --}}
    <span data-icon="bold"><x-icon path="ph.bold.text-b-bold" /></span>
    <span data-icon="italic"><x-icon path="ph.bold.text-italic-bold" /></span>
    <span data-icon="underline"><x-icon path="ph.bold.text-underline-bold" /></span>
    <span data-icon="strikethrough"><x-icon path="ph.bold.text-strikethrough-bold" /></span>

    {{-- Headings --}}
    <span data-icon="heading"><x-icon path="ph.bold.text-h-bold" /></span>
    <span data-icon="heading-1"><x-icon path="ph.bold.text-h-one-bold" /></span>
    <span data-icon="heading-2"><x-icon path="ph.bold.text-h-two-bold" /></span>
    <span data-icon="heading-3"><x-icon path="ph.bold.text-h-three-bold" /></span>
    <span data-icon="paragraph"><x-icon path="ph.bold.paragraph-bold" /></span>

    {{-- Code --}}
    <span data-icon="code"><x-icon path="ph.bold.code-bold" /></span>
    <span data-icon="inline-code"><x-icon path="ph.bold.code-simple-bold" /></span>

    {{-- Block --}}
    <span data-icon="quote"><x-icon path="ph.bold.quotes-bold" /></span>
    <span data-icon="horizontal-rule"><x-icon path="ph.bold.minus-bold" /></span>

    {{-- Lists --}}
    <span data-icon="unordered-list"><x-icon path="ph.bold.list-bullets-bold" /></span>
    <span data-icon="ordered-list"><x-icon path="ph.bold.list-numbers-bold" /></span>
    <span data-icon="task-list"><x-icon path="ph.bold.list-checks-bold" /></span>

    {{-- Links --}}
    <span data-icon="link"><x-icon path="ph.bold.link-bold" /></span>
    <span data-icon="unlink"><x-icon path="ph.bold.link-break-bold" /></span>
    <span data-icon="external-link"><x-icon path="ph.bold.arrow-square-out-bold" /></span>

    {{-- Media --}}
    <span data-icon="image"><x-icon path="ph.bold.image-bold" /></span>
    <span data-icon="youtube"><x-icon path="ph.bold.youtube-logo-bold" /></span>

    {{-- Table --}}
    <span data-icon="table"><x-icon path="ph.bold.table-bold" /></span>
    <span data-icon="add-col-after"><x-icon path="ph.bold.plus-bold" /></span>
    <span data-icon="add-row-after"><x-icon path="ph.bold.plus-bold" /></span>
    <span data-icon="delete-col"><x-icon path="ph.bold.x-bold" /></span>
    <span data-icon="delete-row"><x-icon path="ph.bold.x-bold" /></span>
    <span data-icon="merge-cells"><x-icon path="ph.bold.intersect-bold" /></span>

    {{-- Alignment --}}
    <span data-icon="align-left"><x-icon path="ph.bold.text-align-left-bold" /></span>
    <span data-icon="align-center"><x-icon path="ph.bold.text-align-center-bold" /></span>
    <span data-icon="align-right"><x-icon path="ph.bold.text-align-right-bold" /></span>

    {{-- Misc --}}
    <span data-icon="highlight"><x-icon path="ph.bold.highlighter-bold" /></span>
    <span data-icon="superscript"><x-icon path="ph.bold.text-superscript-bold" /></span>
    <span data-icon="subscript"><x-icon path="ph.bold.text-subscript-bold" /></span>
    <span data-icon="translation"><x-icon path="ph.bold.translate-bold" /></span>
    <span data-icon="clear"><x-icon path="ph.bold.eraser-bold" /></span>

    {{-- UI --}}
    <span data-icon="fullscreen"><x-icon path="ph.bold.arrows-out-bold" /></span>
    <span data-icon="exit-fullscreen"><x-icon path="ph.bold.arrows-in-bold" /></span>
    <span data-icon="chevron-down"><x-icon path="ph.bold.caret-down-bold" /></span>
    <span data-icon="undo"><x-icon path="ph.bold.arrow-counter-clockwise-bold" /></span>
    <span data-icon="redo"><x-icon path="ph.bold.arrow-clockwise-bold" /></span>
    <span data-icon="trash"><x-icon path="ph.bold.trash-bold" /></span>
    <span data-icon="pencil"><x-icon path="ph.bold.pencil-simple-bold" /></span>
</template>

{{-- Editor i18n --}}
@php
    $_ek = [
        'bold',
        'italic',
        'underline',
        'strikethrough',
        'heading',
        'heading_1',
        'heading_2',
        'heading_3',
        'paragraph',
        'code',
        'inline_code',
        'quote',
        'unordered_list',
        'ordered_list',
        'task_list',
        'horizontal_rule',
        'link',
        'image',
        'table',
        'fullscreen',
        'clear',
        'highlight',
        'youtube',
        'undo',
        'redo',
        'superscript',
        'subscript',
        'align',
        'align_left',
        'align_center',
        'align_right',
        'left',
        'center',
        'right',
        'edit',
        'open',
        'remove',
        'small',
        'medium',
        'full_width',
        'float_left',
        'float_right',
        'alt_text',
        'delete',
        'add_column',
        'add_row',
        'delete_column',
        'delete_row',
        'merge_cells',
        'delete_table',
        'insert_table',
        'edit_table',
        'video_url',
        'insert',
        'cancel',
        'apply',
        'save',
        'title',
        'link_title',
        'open_in_new_tab',
        'image_alt_text',
        'describe_image',
        'insert_image',
        'create_link',
        'words',
        'chars',
        'toggle_header_row',
        'toggle_header_col',
        'insert_left',
        'insert_right',
        'insert_above',
        'insert_below',
    ];
    $_ei = [];
    foreach ($_ek as $_k) {
        $_v = __('editor.' . $_k);
        if ($_v !== 'editor.' . $_k) {
            $_ei[$_k] = $_v;
        }
    }
@endphp
<script>
    window.FluteRichText = window.FluteRichText || {};
    window.FluteRichText.i18n = {!! json_encode($_ei, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
</script>

{{-- Modal templates for TipTap Rich Text Editor --}}

{{-- Link modal --}}
<template id="tiptap-modal-link-tpl">
    <div class="modal modal--sm" role="dialog" aria-hidden="true" data-a11y-dialog>
        <div class="modal__overlay" tabindex="-1" data-a11y-dialog-hide></div>
        <div class="modal__container" role="document">
            <header class="modal__header">
                <h4 class="modal__title">{{ __('editor.link') }}</h4>
                <button class="modal__close" data-a11y-dialog-hide></button>
            </header>
            <div class="modal__content">
                <div class="form__group">
                    <label class="form__label">URL</label>
                    <div class="input__field-container">
                        <input type="url" class="input__field" data-field="url" placeholder="https://...">
                    </div>
                </div>
                <div class="form__group">
                    <label class="form__label">{{ __('editor.title') }}</label>
                    <div class="input__field-container">
                        <input type="text" class="input__field" data-field="title"
                            placeholder="{{ __('editor.link_title') }}">
                    </div>
                </div>
                <div class="form__group">
                    <input type="checkbox" class="checkbox__field" data-field="blank">
                    <label class="checkbox__label">{{ __('editor.open_in_new_tab') }}</label>
                </div>
            </div>
            <footer class="modal__footer">
                <button type="button" class="btn btn-error btn-small w-100"
                    data-modal-action="unlink">{{ __('editor.remove') }}</button>
                <button type="button" class="btn btn-outline-primary btn-small w-100"
                    data-modal-action="cancel">{{ __('editor.cancel') }}</button>
                <button type="button" class="btn btn-primary btn-small w-100"
                    data-modal-action="apply">{{ __('editor.apply') }}</button>
            </footer>
        </div>
    </div>
</template>

{{-- Table insert modal --}}
<template id="tiptap-modal-table-tpl">
    <div class="modal modal--sm" role="dialog" aria-hidden="true" data-a11y-dialog>
        <div class="modal__overlay" tabindex="-1" data-a11y-dialog-hide></div>
        <div class="modal__container" role="document">
            <header class="modal__header">
                <h4 class="modal__title">{{ __('editor.insert_table') }}</h4>
                <button class="modal__close" data-a11y-dialog-hide></button>
            </header>
            <div class="modal__content">
                <div>
                    <div class="table-grid-picker"></div>
                    <div class="table-grid-label">0 &times; 0</div>
                </div>
            </div>
        </div>
    </div>
</template>

{{-- YouTube modal --}}
<template id="tiptap-modal-youtube-tpl">
    <div class="modal modal--sm" role="dialog" aria-hidden="true" data-a11y-dialog>
        <div class="modal__overlay" tabindex="-1" data-a11y-dialog-hide></div>
        <div class="modal__container" role="document">
            <header class="modal__header">
                <h4 class="modal__title">{{ __('editor.youtube') }}</h4>
                <button class="modal__close" data-a11y-dialog-hide></button>
            </header>
            <div class="modal__content">
                <div class="form__group">
                    <label class="form__label">{{ __('editor.video_url') }}</label>
                    <div class="input__field-container">
                        <input type="url" class="input__field" data-field="url"
                            placeholder="https://youtube.com/watch?v=...">
                    </div>
                </div>
            </div>
            <footer class="modal__footer">
                <button type="button" class="btn btn-outline-primary btn-small w-100"
                    data-modal-action="cancel">{{ __('editor.cancel') }}</button>
                <button type="button" class="btn btn-primary btn-small w-100"
                    data-modal-action="insert">{{ __('editor.insert') }}</button>
            </footer>
        </div>
    </div>
</template>

{{-- Image alt text modal --}}
<template id="tiptap-modal-image-alt-tpl">
    <div class="modal modal--sm" role="dialog" aria-hidden="true" data-a11y-dialog>
        <div class="modal__overlay" tabindex="-1" data-a11y-dialog-hide></div>
        <div class="modal__container" role="document">
            <header class="modal__header">
                <h4 class="modal__title">{{ __('editor.image_alt_text') }}</h4>
                <button class="modal__close" data-a11y-dialog-hide></button>
            </header>
            <div class="modal__content">
                <div class="form__group">
                    <label class="form__label">{{ __('editor.alt_text') }}</label>
                    <div class="input__field-container">
                        <input type="text" class="input__field" data-field="alt"
                            placeholder="{{ __('editor.describe_image') }}">
                    </div>
                </div>
            </div>
            <footer class="modal__footer">
                <button type="button" class="btn btn-outline-primary btn-small w-100"
                    data-modal-action="cancel">{{ __('editor.cancel') }}</button>
                <button type="button" class="btn btn-primary btn-small w-100"
                    data-modal-action="save">{{ __('editor.save') }}</button>
            </footer>
        </div>
    </div>
</template>
