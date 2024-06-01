$(function() {
    $('.device-endsession').on('click', function () {
        var deviceId = $(this).data('id');
        var button = $(this);

        $.ajax({
            url: u('profile/edit/deletedevice'),
            type: 'DELETE',
            data: { id: deviceId },
            success: function (response) {
                button.closest('tr').remove();
                toast({
                    message: translate('def.success'),
                    type: 'success',
                });
            },
            error: function (xhr, status, error) {
                console.error('Error ending device session: ' + error);
                toast({
                    message: error,
                    type: 'error',
                    duration: 10000,
                });
            },
        });
    });
});
