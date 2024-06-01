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

    $(document).on('click', '.social-action-buttons .action-button.delete', async function () {
        let socialId = $(this).data('deletesocial');
        if (await asyncConfirm(translate('admin.socials.confirm_delete')))
            ajaxModuleAction(u('admin/api/socials/' + socialId), 'DELETE');
    });

    // Handle disable social action
    $(document).on('click', '.social-action-buttons .action-button.disable', function () {
        let socialId = $(this).data('disablesocial');
        ajaxModuleAction(u('admin/api/socials/disable/' + socialId), 'POST');
    });

    // Handle enable social action
    $(document).on('click', '.social-action-buttons .action-button.activate', function () {
        let socialId = $(this).data('activatesocial');
        ajaxModuleAction(u('admin/api/socials/enable/' + socialId), 'POST');
    });
});
