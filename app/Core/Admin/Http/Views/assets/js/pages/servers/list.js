$(function () {
    $(document).on(
        'click',
        '.servers-action-buttons .action-button.delete',
        async function () {
            let serverId = $(this).data('deleteserver');
            if (await asyncConfirm(translate('admin.servers.confirm_delete')))
                sendRequest({}, u('admin/api/servers/' + serverId), 'DELETE');
        },
    );
});
