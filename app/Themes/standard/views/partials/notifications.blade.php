<div class="notifications__main" data-remove-handler>
    <header class="right_sidebar__header">
        <h5 class="right_sidebar__title">
            @t('def.notifications')
        </h5>
        <button class="right_sidebar__close" aria-label="Close modal" data-a11y-dialog-hide="right-sidebar"
            data-original-tabindex="null"></button>
    </header>
    <div class="notifications__tabs">
        <x-tabs name="notifications">
            <x-slot:headings>
                <x-tab-heading name="unread" label="{{ __('def.not_read') }}"
                    url="{{ url('sidebar/notifications/unread') }}" badge="{{ $countUnread ?? 0 }}" active="true"
                    data-no-close />
                <x-tab-heading name="all" url="{{ url('sidebar/notifications/all') }}" label="{{ __('def.all') }}"
                    badge="{{ $countAll ?? 0 }}" data-no-close />
            </x-slot:headings>

            <x-tab-body>
                <x-tab-content name="unread" active="true">
                    @for ($i = 0; $i < 3; $i++)
                        <div class="notifications__skeleton">
                            <div class="skeleton notifications__skeleton-icon"></div>
                            <div class="notifications__skeleton-content">
                                <div class="skeleton notifications__skeleton-title"></div>
                                <div class="skeleton notifications__skeleton-text"></div>
                                <div class="skeleton notifications__skeleton-date"></div>
                            </div>
                        </div>
                    @endfor
                </x-tab-content>

                <x-tab-content name="all">
                    @for ($i = 0; $i < 3; $i++)
                        <div class="notifications__skeleton">
                            <div class="skeleton notifications__skeleton-icon"></div>
                            <div class="notifications__skeleton-content">
                                <div class="skeleton notifications__skeleton-title"></div>
                                <div class="skeleton notifications__skeleton-text"></div>
                                <div class="skeleton notifications__skeleton-date"></div>
                            </div>
                        </div>
                    @endfor
                </x-tab-content>
            </x-tab-body>
        </x-tabs>
    </div>
</div>

@stack('right-sidebar')
