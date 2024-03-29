$(document).ready(function () {
    $(document).on('submit', '#add, #edit', (ev) => {
        let $form = $(ev.currentTarget);

        ev.preventDefault();
    
        let path = $form.attr('id'), form = serializeForm($form);

        let url = `admin/api/footer/socials/${path}`,
            method = 'POST';
    
        if (path === 'edit') {
            url = `admin/api/footer/socials/${form.id}`;
            method = 'PUT';
        }
    
        if (ev.target.checkValidity()) {
            sendRequest(form, url, method);
        }
    });
});
