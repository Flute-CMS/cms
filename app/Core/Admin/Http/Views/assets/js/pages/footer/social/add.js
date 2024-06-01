$(function() {
    $(document).on('submit', '#footerAdds, #footerEdit', (ev) => {
        let $form = $(ev.currentTarget);

        ev.preventDefault();
    
        let path = $form.attr('id') === 'footerAdds' ? 'add' : 'edit', form = serializeForm($form);

        let url = `admin/api/footer/socials/${path}`,
            method = 'POST';
    
        if (path === 'edit') {
            url = `admin/api/footer/socials/${form.id}`;
            method = 'PUT';
        }

        console.log($form, form, path);
    
        if (ev.target.checkValidity()) {
            sendRequest(form, url, method);
        }
    });
});
