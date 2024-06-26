$(function () {
    
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
