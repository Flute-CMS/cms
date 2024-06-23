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
