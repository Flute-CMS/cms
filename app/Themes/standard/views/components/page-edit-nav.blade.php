<div class="pe-topbar" id="page-edit-nav">
    <div class="pe-topbar__inner">
        {{-- Left: Back + title + undo/redo --}}
        <div class="pe-topbar__left">
            <button class="pe-topbar__back" id="page-change-cancel" type="button" data-tooltip="{{ __('def.cancel') }}">
                <x-icon path="ph.regular.arrow-left" />
            </button>
            <div class="pe-topbar__title">
                <span class="pe-topbar__label">{{ __('page-edit.editing') }}</span>
                <span class="pe-topbar__page">{{ request()->getPathInfo() ?: '/' }}</span>
            </div>
            <div class="pe-topbar__divider"></div>
            <div class="pe-topbar__history">
                <button class="pe-topbar__btn" id="page-edit-undo" disabled data-tooltip="{{ __('page-edit.undo') }}">
                    <x-icon path="ph.regular.arrow-bend-up-left" />
                </button>
                <button class="pe-topbar__btn" id="page-edit-redo" disabled data-tooltip="{{ __('page-edit.redo') }}">
                    <x-icon path="ph.regular.arrow-bend-up-right" />
                </button>
            </div>
        </div>

        {{-- Center: Scope toggle --}}
        <div class="pe-topbar__center">
            <div class="pe-scope-toggle" id="page-edit-scope-toggle">
                <button type="button" class="pe-scope-toggle__btn active" data-scope="local" data-tooltip="{{ __('page.layout_local_hint') }}">
                    <x-icon path="ph.regular.file" />
                    <span>{{ __('page.layout_local') }}</span>
                </button>
                <button type="button" class="pe-scope-toggle__btn" data-scope="global" data-tooltip="{{ __('page.layout_global_hint') }}">
                    <x-icon path="ph.regular.globe" />
                    <span>{{ __('page.layout_global') }}</span>
                </button>
            </div>
        </div>

        {{-- Right: Actions --}}
        <div class="pe-topbar__right">
            <button class="pe-topbar__btn" id="page-edit-auto-position" data-tooltip="{{ __('page.edit_nav.auto_position') }}">
                <x-icon path="ph.regular.magic-wand" />
            </button>
            <div class="pe-topbar__divider"></div>
            <button class="pe-topbar__discard" id="page-edit-reset" type="button">
                {{ __('page-edit.discard') }}
            </button>
            <button class="pe-topbar__save" id="page-edit-save" type="button">
                <x-icon path="ph.regular.check" />
                <span>{{ __('def.save') }}</span>
            </button>
        </div>
    </div>
</div>
