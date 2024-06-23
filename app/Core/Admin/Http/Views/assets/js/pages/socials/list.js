$(function () {
    $(document).on(
        'click',
        '.social-action-buttons .action-button.delete',
        async function () {
            let socialId = $(this).data('deletesocial');
            if (await asyncConfirm(translate('admin.socials.confirm_delete')))
                sendRequest({}, u('admin/api/socials/' + socialId), 'DELETE');
        },
    );

    // Handle disable social action
    $(document).on(
        'click',
        '.social-action-buttons .action-button.disable',
        function () {
            let socialId = $(this).data('disablesocial');
            sendRequest({}, u('admin/api/socials/disable/' + socialId), 'POST');
        },
    );

    // Handle enable social action
    $(document).on(
        'click',
        '.social-action-buttons .action-button.activate',
        function () {
            let socialId = $(this).data('activatesocial');
            sendRequest({}, u('admin/api/socials/enable/' + socialId), 'POST');
        },
    );
});
