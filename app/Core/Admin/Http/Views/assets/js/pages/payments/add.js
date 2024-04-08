document.addEventListener('DOMContentLoaded', function () {
    let paramIndex = 0;

    $('#addParam').click(function () {
        paramIndex++;
        appendParamFields(paramIndex);
    });

    function appendParamFields(index, key = '', value = '') {
        $('#parametersContainer').append(`
            <div class="param-group" id="param-group-${index}">
                <input type="text" name="paramNames[]" class="form-control" placeholder="Key" value="${key}" required>
                <input type="text" name="paramValues[]" class="form-control" placeholder="Value" value="${value}" required>
                <button type="button" class="removeParam btn size-s error" data-id="${index}">${translate('def.delete')}</button>
            </div>
        `);
    }

    $(document).on('click', '.removeParam', function () {
        let id = $(this).data('id');
        $('#param-group-' + id).remove();
    });

    $(document).on('submit', '#add, #edit', (ev) => {
        let $form = $(ev.currentTarget);

        ev.preventDefault();

        let path = $form.attr('id'),
            form = serializeForm($form);

        let url = `admin/api/payments/${path}`,
            method = 'POST';

        if (path === 'edit') {
            url = `admin/api/payments/${form.id}`;
            method = 'PUT';
        }

        if (ev.target.checkValidity()) {
            sendRequest(form, url, method);
        }
    });

    $('#adapter').change(function () {
        var paymentSystem = $(this).val();
        var handleUrl = u(`api/lk/handle/${paymentSystem}`); // Example URL format
        $('#handleUrl').val(handleUrl);
        updateParameters();
    });

    var adapterSelect = document.getElementById('adapter');
    var parametersContainer = document.getElementById('parametersContainer');

    function updateParameters() {
        var selectedGatewayKey = adapterSelect.value;
        var params = drivers[selectedGatewayKey]?.parameters || [];

        $('#parametersContainer').empty();

        params.forEach(function (param) {
            paramIndex++;
            appendParamFields(paramIndex, param, ''); // Append new parameter fields
        });
    }

    if (!$('#parametersContainer').hasClass('parametersEdit'))
        updateParameters();

    adapterSelect.addEventListener('change', updateParameters);
});
