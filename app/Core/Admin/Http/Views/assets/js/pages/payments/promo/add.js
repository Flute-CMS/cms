$(function() {
    $(document).on('submit', '#promoAdd, #promoEdit', (ev) => {
        let $form = $(ev.currentTarget);

        ev.preventDefault();

        let path = $form.attr('id') === 'promoAdd' ? 'add' : 'edit', form = serializeForm($form);

        let url = `admin/api/payments/promo/${path}`,
            method = 'POST';
    
        if (path === 'edit') {
            url = `admin/api/payments/promo/${form.id}`;
            method = 'PUT';
        }
    
        if (ev.target.checkValidity()) {
            sendRequest(form, url, method);
        }
    });
});
