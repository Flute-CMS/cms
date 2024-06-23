$(function() {
    $(document).on('click', '.payment-promo-action-buttons .action-button.delete', async function () {
        let paymentId = $(this).data('deletepromo');
        if (await asyncConfirm(translate('admin.payments.promo.confirm_delete')))
            sendRequest({}, u('admin/api/payments/promo/' + paymentId), 'DELETE');
    });
});
