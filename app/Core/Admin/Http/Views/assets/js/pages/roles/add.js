$(document).on('submit', '#roleAdd, #roleEdit', (ev) => {
    let $form = $(ev.currentTarget);

    ev.preventDefault();
    let path = $form.attr('id') === 'roleAdd' ? 'add' : 'edit',
        form = serializeForm($form);

    let url = `admin/api/roles/${path}`,
        method = 'POST';

    if (path === 'edit') {
        url = `admin/api/roles/${form.id}`;
        method = 'PUT';
    }

    if (ev.target.checkValidity()) {
        sendRequest(form, url, method);
    }
});