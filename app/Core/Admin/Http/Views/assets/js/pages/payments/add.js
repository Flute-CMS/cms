$(function() {
    let paramIndex = 0;

    $(document).on('click', '#addParam', function () {
        paramIndex++;
        appendParamFields(paramIndex);
    });

    function appendParamFields(index, key = '', value = '') {
        $('#paymentsParametersContainer').append(`
            <div class="param-group" id="param-group-${index}">
                <input type="text" name="paramNames[]" class="form-control" placeholder="Key" value="${key}" required>
                <input type="text" name="paramValues[]" class="form-control" placeholder="Value" value="${value}" required>
                <button type="button" class="removeParam btn size-s error" data-id="${index}">${translate('def.delete')}</button>
            </div>
        `);
    }

    $(document).on('click', '#paymentsParametersContainer .removeParam', function () {
        let id = $(this).data('id');
        $('#param-group-' + id).remove();
    });

    $(document).on('submit', '#gatewayAdd, #gatewayEdit', (ev) => {
        let $form = $(ev.currentTarget);

        ev.preventDefault();

        let path = $form.attr('id') === 'gatewayAdd' ? 'add' : 'edit',
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

    $(document).on('change', '#adapter', function () {
        var paymentSystem = $(this).val();
        var handleUrl = u(`api/lk/handle/${paymentSystem}`); // Example URL format
        $('#handleUrl').val(handleUrl);
        updateParameters();
    });

    var adapterSelect = document.getElementById('adapter');
    var parametersContainer = document.getElementById('paymentsParametersContainer');

    function updateParameters() {
        var selectedGatewayKey = adapterSelect.value;
        var params = drivers[selectedGatewayKey]?.parameters || [];

        $('#paymentsParametersContainer').empty();

        params.forEach(function (param) {
            paramIndex++;
            appendParamFields(paramIndex, param, ''); // Append new parameter fields
        });
    }

    if (!$('#paymentsParametersContainer').hasClass('parametersEdit'))
        updateParameters();

    adapterSelect.addEventListener('change', updateParameters);
});
