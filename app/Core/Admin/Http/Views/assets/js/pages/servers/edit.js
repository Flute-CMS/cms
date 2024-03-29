$(document).on('submit', '#editServer', (ev) => {
    let $form = $(ev.currentTarget);

    ev.preventDefault();

    if (ev.target.checkValidity()) {
        sendRequest(serializeForm($form), `admin/api/servers/${$form.data('sid')}`, 'PUT');
    }
});