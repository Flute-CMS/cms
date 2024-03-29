function serializeForm($form) {
    let formData = $form.serializeArray();
    let paramObj = {};
    let additionalParams = {};

    // Process standard fields
    formData.forEach(function (kv) {
        if (kv.name === 'paramNames[]' || kv.name === 'paramValues[]') {
            // Skip processing here, handle in additional parameters
        } else {
            paramObj[kv.name] = kv.value;
        }
    });

    // Process dynamic additional parameters
    let paramNames = $form
        .find('input[name="paramNames[]"]')
        .map(function () {
            return $(this).val();
        })
        .get();
    let paramValues = $form
        .find('input[name="paramValues[]"]')
        .map(function () {
            return $(this).val();
        })
        .get();

    // Добавляем неотмеченные чекбоксы
    $form.find('input[type="checkbox"]').each(function () {
        paramObj[this.name] = this.checked;
    });

    paramNames.forEach(function (name, index) {
        if (name) {
            // Only add parameter if name is not empty
            additionalParams[name] = paramValues[index] || '';
        }
    });

    // Assign additional parameters to a specific key, or directly to paramObj
    paramObj.additional = additionalParams;

    return paramObj;
}

function sendRequest(data, path = null, method = 'POST') {
    let result = null;

    $.ajax({
        url: u(path),
        type: method,
        data: data,
        async: false,
        success: function (response) {
            toast({
                message: response?.success || translate('def.success'),
                type: 'success',
            });

            result = response;

            Modals.clear();

            if (method === 'DELETE') {
                window.location.reload();
            } else {
                if (!path.includes('admin/api/settings')) {
                    $('button[type="submit"]').attr('disabled', true);

                    setTimeout(() => {
                        if ('referrer' in document) {
                            window.location = document.referrer;
                        } else {
                            window.history.back();
                        }
                    }, 2000);
                }
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.error('error request', jqXHR, textStatus, errorThrown);
            toast({
                message:
                    jqXHR.responseJSON?.error ?? translate('def.unknown_error'),
                type: 'error',
            });

            result = jqXHR.responseJSON;
        },
    });

    return result;
}

$(document).ready(function () {
    $(document).on('submit', '[data-form]', (ev) => {
        let $form = $(ev.currentTarget);

        ev.preventDefault();

        let path = $form.data('form'),
            form = serializeForm($form),
            page = $form.data('page');

        let url = `admin/api/${page}/${path}`,
            method = 'POST';

        if (path === 'edit') {
            url = `admin/api/${page}/${form.id}`;
            method = 'PUT';
        }

        if (ev.target.checkValidity()) {
            sendRequest(form, url, method);
        }
    });

    $(document).on('click', '[data-deleteaction]', function () {
        let id = $(this).data('deleteaction'),
            path = $(this).data('deletepath');

        if (confirm(translate('admin.confirm_delete'))) {
            sendRequest({}, 'admin/api/' + path + '/' + id, 'DELETE');

            // $(this).parent().parent().parent().remove();
        }
    });
});
