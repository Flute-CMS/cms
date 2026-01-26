<div class="page-edit-nav">
    <div class="page-edit-nav-content">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <div class="page-edit-nav-block">
                        <h6>{{ __('page.edit_nav.title', ['url' => request()->getBaseUrl()]) }}</h6>
                        <x-button type="outline-primary" size="small" id="page-edit-undo" disabled>
                            <x-icon path="ph.regular.arrow-bend-up-left" />
                        </x-button>
                        <x-button type="outline-primary" size="small" id="page-edit-redo" disabled>
                            <x-icon path="ph.regular.arrow-bend-up-right" />
                        </x-button>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="page-edit-nav-block scope-toggle">
                        <div class="page-edit-scope-toggle" id="page-edit-scope-toggle">
                            <button type="button" class="scope-btn active" data-scope="local" data-tooltip="{{ __('page.layout_local_hint') }}">
                                <x-icon path="ph.regular.file" />
                                <span>{{ __('page.layout_local') }}</span>
                            </button>
                            <button type="button" class="scope-btn" data-scope="global" data-tooltip="{{ __('page.layout_global_hint') }}">
                                <x-icon path="ph.regular.globe" />
                                <span>{{ __('page.layout_global') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="page-edit-nav-block save">
                        <x-button class="page-edit-cancel" id="page-change-cancel" size="medium"
                            type="outline-primary">
                            {{ __('def.cancel') }}
                        </x-button>
                        <x-button class="page-edit-save" size="medium" type="accent" id="page-edit-save">
                            <x-icon path="ph.regular.check-circle" />
                            {{ __('def.save') }}
                        </x-button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
