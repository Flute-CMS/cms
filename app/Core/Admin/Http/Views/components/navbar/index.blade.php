<div class="content-header">
    @admin_navbar_contact
    @admin_navbar_search
    @admin_navbar_version

    @if (user()->hasPermission('admin.system'))
        @admin_navbar_log
    @endif
</div>
