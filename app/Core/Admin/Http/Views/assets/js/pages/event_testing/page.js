$(function () {
    events.forEach((event) => {
        $('#eventSelect').append(
            `<option value="${event.name}">${event.label}</option>`,
        );
    });

    $(document).on('change', '#eventSelect', function () {
        let selectedEvent = $(this).val();
        let event = events.find((e) => e.name === selectedEvent);
        let parametersHtml = '';

        event.parameters.forEach((param) => {
            if (param.name === 'user_id') {
                parametersHtml += `<div class="position-relative row form-group">
                    <div class="col-sm-3 col-form-label required">
                        <label>${param.label}</label>
                    </div>
                    <div class="col-sm-9">
                        <input type="${
                            param.type
                        }" class="form-control" name="${
                    param.name
                }" placeholder="${param.label}" />
                        <a type="button" class="enterMine" onclick="setUserId('${
                            param.name
                        }')">${translate('admin.event_testing.enter_mine')}</a>
                    </div>
                    <div class="col-sm-3">
                    </div>
                </div>`;
            } else {
                parametersHtml += `<div class="position-relative row form-group">
                    <div class="col-sm-3 col-form-label required">
                        <label>${param.label}</label>
                    </div>
                    <div class="col-sm-9">
                        <input type="${param.type}" class="form-control" name="${param.name}" placeholder="${param.label}" />
                    </div>
                </div>`;
            }
        });

        $('#eventParameters').html(parametersHtml);
        $('#checkEvent').attr('disabled', true);
        validateForm();
    });

    window.setUserId = function (paramName) {
        $(`input[name="${paramName}"]`).val(currentUserId);
        validateForm();
    };

    function validateForm() {
        let allFilled = true;
        $('#eventParameters input').each(function () {
            if ($(this).val() === '') {
                allFilled = false;
            }
        });

        if (allFilled) {
            $('#checkEvent').attr('disabled', false);
        } else {
            $('#checkEvent').attr('disabled', true);
        }
    }

    $(document).on('input', '#eventParameters input', function () {
        validateForm();
    });

    $(document).on('click', '#checkEvent', function () {
        let selectedEvent = $('#eventSelect').val();
        let params = {};

        $('#eventParameters input').each(function () {
            params[$(this).attr('name')] = $(this).val();
        });

        let requestData = {
            event: selectedEvent,
            params: params,
        };

        toast({
            type: 'async',
            message: translate('admin.is_loading'),
            fetchFunction: () =>
                new Promise((resolve, reject) => {
                    $.ajax({
                        url: u('admin/api/event_testing/check'),
                        type: 'POST',
                        data: requestData,
                        success: function (data) {
                            $('#eventSelect').val('');
                            $('#eventParameters').html('');
                            $('#checkEvent').attr('disabled', true);

                            resolve(data?.message || translate('def.success'));
                        },
                        error: function (response) {
                            reject(
                                response.responseJSON?.error ??
                                    translate('def.unknown_error'),
                            );
                        },
                    });
                }),
        });
    });
});
