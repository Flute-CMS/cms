document.addEventListener('DOMContentLoaded', function () {
    $(document).on('submit', '#add, #edit', (ev) => {
        let $form = $(ev.currentTarget);

        ev.preventDefault();

        let path = $form.attr('id'), form = serializeForm($form);

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
