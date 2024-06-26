$(function () {

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
