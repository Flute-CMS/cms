<aside class="modal right_sidebar" id="page-seo-dialog" aria-hidden="true" aria-labelledby="page-seo-dialog-title"
    role="dialog" data-a11y-dialog>
    <div class="right_sidebar__overlay" tabindex="-1" data-a11y-dialog-hide></div>
    <div class="right_sidebar__container" id="page-seo-dialog-container" role="dialog" aria-modal="true"
        data-a11y-dialog-ignore-focus-trap>
        <header class="right_sidebar__header">
            <h5 class="right_sidebar__title" id="page-seo-dialog-title">
                @t('page.seo.title')
            </h5>
            <button class="right_sidebar__close" aria-label="Close modal"
                data-a11y-dialog-hide="page-seo-dialog"></button>
        </header>

        <div class="right_sidebar__content w-100 mt-2 h-full" id="page-seo-dialog-content">
            <div class="d-flex flex-column w-100 flex-1 gap-2">
                @for ($i = 0; $i < 6; $i++)
                    <div class="skeleton w-100" style="height: {{ 80 + $i * 10 }}px; flex-grow: 1;"></div>
                @endfor
            </div>
        </div>

        <div class="right_sidebar__footer w-100">
            <x-button type="primary" class="w-100" id="page-seo-save-btn" hx-post="{{ route('pages.saveSEO') }}"
                hx-include="#page-seo-form" hx-swap="none" withLoading>
                @t('def.save')
            </x-button>
        </div>
    </div>
</aside>
