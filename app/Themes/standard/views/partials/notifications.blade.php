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
                    <div class="skeleton notifications__skeleton mt-3"></div>
                    <div class="skeleton notifications__skeleton"></div>
                    <div class="skeleton notifications__skeleton"></div>
                </x-tab-content>

                <x-tab-content name="all">
                    <div class="skeleton notifications__skeleton mt-3"></div>
                    <div class="skeleton notifications__skeleton"></div>
                    <div class="skeleton notifications__skeleton"></div>
                </x-tab-content>
            </x-tab-body>
        </x-tabs>
    </div>
</div>

@stack('right-sidebar')
