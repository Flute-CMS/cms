$(document).on('submit', '#addServer', (ev) => {
    let $formServer = $('#addServer');

    console.log($formServer, serializeForm($formServer))

    ev.preventDefault();

    if (ev.target.checkValidity()) {
        sendRequest(serializeForm($formServer), `admin/api/servers/add`);
    }
});