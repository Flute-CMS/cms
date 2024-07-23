<div class="sidebar-container" id="sidebar-container">
    <div class="sidebar-collapse" id="sidebar-toggle">
        <i class="ph-bold ph-caret-double-right" id="closed-sidebar-icon"></i>
        <i class="ph-bold ph-caret-double-left" id="opened-sidebar-icon"></i>
    </div>
    <div class="sidebar">
        <div class="flex-menu">
            @admin_sidebar_items
            @stack('admin::sidebar')
        </div>
    </div>
</div>
