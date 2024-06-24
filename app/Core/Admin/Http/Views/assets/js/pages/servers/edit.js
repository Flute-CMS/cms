$(document).on('submit', '#editServer', (ev) => {
    let $form = $('#editServer');

    ev.preventDefault();

    if (ev.target.checkValidity()) {
        sendRequest(
            serializeForm($form),
            `admin/api/servers/${$form.data('sid')}`,
            'PUT',
        );
    }
});

$(document).on('click', '#check_ip', (ev) => {
    toast({
        type: 'async',
        message: translate('admin.is_loading'),
        fetchFunction: () =>
            new Promise((resolve, reject) => {
                $.ajax({
                    url: u('admin/api/servers/check-ip'),
                    type: 'POST',
                    data: {
                        ip: $('#serverIp').val(),
                        port: Number($('#serverPort').val()),
                        game: $('#gameSelect').val(),
                    },
                    success: function (response) {
                        Modals.clear();

                        resolve(response?.success || translate('def.success'));
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        let errorMessage = translate('def.unknown_error');
                        if (jqXHR.responseJSON?.error) {
                            const errorObj = jqXHR.responseJSON.error;
                            if (
                                typeof errorObj === 'object' &&
                                errorObj !== null
                            ) {
                                errorMessage = Object.entries(errorObj)
                                    .map(([key, value]) => value.join(', '))
                                    .join('\n');
                            } else {
                                errorMessage = errorObj;
                            }
                        }
                        reject(errorMessage);
                    },
                });
            }),
    });
});

$(document).on('submit', '#commandInputForm', (e) => sendCommand(e));

$(document).on('click', '#openModal', () => {
    Modals.open({
        title: translate('admin.servers.rcon_command_placeholder'),
        content: {
            form: createCommandInput(),
        },
        buttons: [
            {
                text: translate('def.close'),
                class: 'cancel',
                id: 'closeRconBtn',
                callback: (modal) => modal.clear(),
            },
            {
                text: translate('def.submit'),
                class: 'primary',
                id: 'sendBtn',
                callback: () => sendCommand(null),
            },
        ],
    });
});

function createCommandInput() {
    return {
        id: 'commandInputForm',
        fields: [
            {
                type: 'text',
                id: 'commandInput',
                label: translate('admin.servers.rcon_command'),
                placeholder: translate(
                    'admin.servers.rcon_command_placeholder',
                ),
                helpText: translate('admin.servers.rcon_command_desc'),
            },
        ],
    };
}

async function sendCommand(e) {
    if (e) {
        e.preventDefault();
    }

    sendRequest(
        {
            command: $('#commandInput').val(),
            ip: $('#serverIp').val(),
            port: Number($('#serverPort').val()),
            rcon: $('#serverRcon').val(),
            game: $('#gameSelect').val(),
        },
        u('admin/api/servers/check-rcon'),
        'POST',
        function (res) {
            if (res?.result) {
                $('#commandInputForm')
                    .parent()
                    .html(`<pre class='success-message'>${res.result}</pre>`);
                $('#sendBtn').remove();
                $('#closeRconBtn').removeClass('cancel').addClass('primary');
            } else {
                if ($('#commandInputForm > div > .error-message').length > 0) {
                    $('#commandInputForm > div > .error-message').html(
                        res.responseJSON?.error,
                    );
                    $('#commandInputForm > div > .error-message').remove();
                } else {
                    $('#commandInputForm > div').append(
                        `<span class='error-message'>${res.responseJSON?.error}</span>`,
                    );
                }
            }
        },
        false,
    );
}
