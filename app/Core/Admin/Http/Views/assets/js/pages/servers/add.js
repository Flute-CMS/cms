$(document).on('submit', '#addServer', (ev) => {
    let $form = $(ev.currentTarget);

    ev.preventDefault();

    if (ev.target.checkValidity()) {
        sendRequest(serializeForm($form), `admin/api/servers/add`);
    }
});