$(document).on('submit', '#editServer', (ev) => {
    let $form = $('#editServer');

    ev.preventDefault();

    if (ev.target.checkValidity()) {
        sendRequest(serializeForm($form), `admin/api/servers/${$form.data('sid')}`, 'PUT');
    }
});