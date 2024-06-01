$(function () {
    $(document).on(
        'click',
        '.social-action-buttons .action-button.delete',
        async function () {
            let socialId = $(this).data('deletesocial');
            if (
                await asyncConfirm(
                    translate('admin.footer.social_confirm_delete'),
                )
            )
                sendRequest(
                    {},
                    'admin/api/footer/socials/' + socialId,
                    'DELETE',
                );
        },
    );
});
