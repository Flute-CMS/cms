<header class="content-header">
    <div class="content-header-left">
        @admin_navbar_logo
        @admin_navbar_search
    </div>

    <div class="content-header-right">
        @admin_navbar_version

        @if (user()->hasPermission('admin.system'))
            @admin_navbar_log
        @endif

        @admin_navbar_contact
    </div>
</header>

@admin_navbar_tabs
