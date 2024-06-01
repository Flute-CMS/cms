$(function () {
    $(document).on('click', '[data-unblockuser]', async function () {
        const userId = $(this).data('unblockuser');

        if (
            await asyncConfirm(
                translate('admin.users.confirm_unblock'),
                null,
                translate('admin.users.unblock'),
                null,
                'primary',
            )
        ) {
            sendRequest({}, `admin/api/users/${userId}/unblock`, 'POST');
        }
    });
});
