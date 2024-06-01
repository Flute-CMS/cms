$(function() {
    function ajaxModuleAction(url, method, data = {}) {
        $.ajax({
            url: url,
            type: method,
            data: {...data, ...{
                "x-csrf-token": csrfToken
            }},
            success: function (response) {
                toast({
                    type: 'success',
                    message: response.success ?? translate('def.success'),
                });

                refreshCurrentPage()
            },
            error: function (xhr, status, error) {
                toast({
                    type: 'error',
                    message:
                        xhr?.responseJSON?.error ??
                        translate('def.unknown_error'),
                });
            },
        });
    }

    $(document).on('click', '.servers-action-buttons .action-button.delete', async function () {
        let serverId = $(this).data('deleteserver');
        if (await asyncConfirm(translate('admin.servers.confirm_delete')))
            ajaxModuleAction(u('admin/api/servers/' + serverId), 'DELETE');
    });
});
