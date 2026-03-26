<aside class="modal right_sidebar" id="right-sidebar" data-a11y-dialog="right-sidebar" aria-hidden="true">
    <div class="right_sidebar__overlay" tabindex="-1" data-a11y-dialog-hide></div>
    <div class="right_sidebar__container" id="right-sidebar-content" role="dialog"
        aria-modal="true" data-a11y-dialog-ignore-focus-trap>

        @stack('right-sidebar')

        {!! $slot !!}
    </div>
</aside>
