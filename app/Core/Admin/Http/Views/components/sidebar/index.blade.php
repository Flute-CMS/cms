<div class="sidebar">
    @admin_sidebar_logo

    <div class="flex-menu">
        <div class="sidebar-menu">
            {{-- Main menu --}}
            @admin_sidebar_main
            @stack('admin::main-sidebar')

            {{-- Additional Menu  --}}
            @admin_sidebar_add
            @stack('admin::additional-sidebar')
        </div>

        {{-- Recent Menu  --}}
        @admin_sidebar_recent
        @stack('admin::recent-sidebar')
    </div>
</div>

@push('footer')
    @at('Core/Admin/Http/Views/assets/js/components/sidebar.js')
@endpush
