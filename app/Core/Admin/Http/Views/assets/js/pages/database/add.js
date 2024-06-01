function populateModParameters(selectedMod) {
    let parametersHtml = '';

    if (selectedMod) {
        let mod = null;
        Object.keys(mods).forEach((groupName) => {
            const foundMod = mods[groupName].find(
                (m) => m.name === selectedMod,
            );
            if (foundMod) {
                mod = foundMod;
            }
        });

        if (mod) {
            if (mod.parameters) {
                mod.parameters.forEach((param) => {
                    parametersHtml += `
                        <div class="position-relative row form-group">
                            <div class="col-sm-3 col-form-label required">
                                <label>${param.label}</label>
                            </div>
                            <div class="col-sm-9">
                                <input type="${
                                    param.type
                                }" class="form-control" name="${
                        param.name
                    }" placeholder="${param.label}" value="${
                        getDefaultValue(param)
                    }" />
                            </div>
                        </div>`;
                });
            }
            $('#customModParameters').hide();
            $('#editorContainer').hide();
        } else if (selectedMod) {
            parametersHtml = '';
            $('#customModParameters').show();
            $('#editorContainer').show();
        }

        $('#modParameters').html(parametersHtml);
        checkInputs();
    }
}

function getDefaultValue(param) {
    if( $('#databaseId').length && params ) {
        return params[$('#databaseId').val()][param.name] || param.default || '';
    }

    return param.default || '';
}

function serializeFormDatabase() {
    let formData = {};
    let additionalData = {};

    $('#modParameters input').each(function () {
        additionalData[$(this).attr('name')] = $(this).val();
    });

    formData['dbname'] = $('#dbname').val();
    formData['sid'] = $('#sid').val();
    formData['additional'] = JSON.stringify(additionalData);

    return formData;
}

$(document).on('submit', '#databaseForm', function (e) {
    e.preventDefault();

    let formData = serializeFormDatabase();
    const selectedMod = $('#mod').val();

    let mod = null;
    Object.keys(mods).forEach((groupName) => {
        const foundMod = mods[groupName].find((m) => m.name === selectedMod);
        if (foundMod) {
            mod = foundMod;
        }
    });

    if (!mod) {
        formData['mod'] = $('#custom_mod_input').val();
        formData['additional'] = ace
            .edit($('#databaseForm').find('.editor-ace')[0])
            .getValue();
    } else {
        formData['mod'] = selectedMod;
    }

    let path = $('#databaseForm').data('form-type');

    let url = `admin/api/databases/${path}`,
        method = 'POST';

    if (path === 'edit') {
        url = `admin/api/databases/${$('#databaseId').val()}`;
        method = 'PUT';
    }

    if (e.target.checkValidity()) {
        sendRequest(formData, url, method);
    }
});

function checkInputs() {
    let allFilled = true;
    $('#modParameters input').each(function () {
        if (!$(this).val()) {
            allFilled = false;
        }
    });

    if (
        (allFilled && $('#mod').val() !== null) ||
        ($('#mod').val() === 'custom' && $('#customModParameters input').val())
    ) {
        $('#submitButton').prop('disabled', false);
    } else {
        $('#submitButton').prop('disabled', true);
    }
}

$(function () {
    checkInputs();
    const selectedMod = $('#mod').val();
    populateModParameters(selectedMod);

    document
        .querySelector('.chrome-tabs')
        .addEventListener('contentRender', () => {
            checkInputs();
            const selectedMod = $('#mod').val();
            populateModParameters(selectedMod);
        });

    $(document).on('change', '#custom_mod_input', function () {
        if ($(this).val()) {
            $('#editorContainer').show();
        } else {
            $('#editorContainer').hide();
        }
    });

    $(document).on('change', '#mod', function () {
        const selectedMod = $(this).val();
        populateModParameters(selectedMod);
    });
    
    $(document).on('input', '#custom_mod_input, #modParameters input', function () {
        checkInputs();
    });
});
