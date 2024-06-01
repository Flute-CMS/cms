$(function () {
    $(document).on('click', '.composer-button', async function (e) {
        let package = $(this).data('install');

        e.preventDefault();
        e.stopPropagation();

        if (
            await asyncConfirm(
                translate('admin.confirm_install'),
                null,
                translate('def.install'),
                null,
                'primary',
            )
        ) {
            sendRequest(
                {
                    package,
                },
                'admin/api/composer/install',
                'POST',
            );
        }
    });
    $(document).on(
        'click',
        '.action-button.delete[data-deletepath="composer"]',
        async function (e) {
            let package = $(this).data('deletepackage');

            e.preventDefault();
            e.stopPropagation();

            if (await asyncConfirm(translate('admin.confirm_delete'))) {
                displayLoading(true);
                sendRequest(
                    {
                        package: package,
                    },
                    'admin/api/composer/uninstall',
                    'DELETE',
                );
            }
        },
    );
});
