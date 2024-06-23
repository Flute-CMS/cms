$(function () {
    $(document).on(
        'click',
        '.payment-action-buttons .action-button.delete',
        async function () {
            let paymentId = $(this).data('deletepayment');
            if (await asyncConfirm(translate('admin.payments.confirm_delete')))
                sendRequest({}, u('admin/api/payments/' + paymentId), 'DELETE');
        },
    );

    // Handle disable payment action
    $(document).on(
        'click',
        '.payment-action-buttons .action-button.disable',
        function () {
            let paymentId = $(this).data('disablepayment');
            sendRequest(
                {},
                u('admin/api/payments/disable/' + paymentId),
                'POST',
            );
        },
    );

    // Handle enable payment action
    $(document).on(
        'click',
        '.payment-action-buttons .action-button.activate',
        function () {
            let paymentId = $(this).data('activatepayment');
            sendRequest(
                {},
                u('admin/api/payments/enable/' + paymentId),
                'POST',
            );
        },
    );
});
