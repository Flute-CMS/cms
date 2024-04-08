$(document).on('click', '.composer-button', function () {
    let package = $(this).data('install');

    if (confirm(translate('admin.confirm_install'))) {
        sendRequest(
            {
                package,
            },
            'admin/api/composer/install',
            'POST',
        );
    }
});
$(document).on('click', '[data-deletepath="composer"]', function (e) {
    let package = $(this).data('deleteaction');

    if (confirm(translate('admin.confirm_delete'))) {
        sendRequest(
            {
                package,
            },
            'admin/api/composer/uninstall',
            'DELETE',
        );

        e.preventDefault();
        e.stopPropagation();
    }
});
