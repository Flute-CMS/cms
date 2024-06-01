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

    $(document).on('click', '.payment-action-buttons .action-button.delete', async function () {
        let paymentId = $(this).data('deletepayment');
        if (await asyncConfirm(translate('admin.payments.confirm_delete')))
            ajaxModuleAction(u('admin/api/payments/' + paymentId), 'DELETE');
    });

    // Handle disable payment action
    $(document).on('click', '.payment-action-buttons .action-button.disable', function () {
        let paymentId = $(this).data('disablepayment');
        ajaxModuleAction(u('admin/api/payments/disable/' + paymentId), 'POST');
    });

    // Handle enable payment action
    $(document).on('click', '.payment-action-buttons .action-button.activate', function () {
        let paymentId = $(this).data('activatepayment');
        ajaxModuleAction(u('admin/api/payments/enable/' + paymentId), 'POST');
    });
});
