$(function () {
    $(document).on('change', '#key', function () {
        let socialPlatform = $(this).val();
        let redirectUri1 = u(`social/${socialPlatform}`);
        let redirectUri2 = u(`profile/social/bind/${socialPlatform}`);
        $('#redirectUri1').val(redirectUri1).attr('data-copy', redirectUri1);
        $('#redirectUri2').val(redirectUri2).attr('data-copy', redirectUri2);
    });
    
    $(document).on('submit', '#socialAdd, #socialEdit', (ev) => {
        let $form = $(ev.currentTarget);

        ev.preventDefault();
        let path =
                $(ev.currentTarget).attr('id') === 'socialAdd' ? 'add' : 'edit',
            form = serializeForm($form);

        let url = `admin/api/socials/${path}`,
            method = 'POST';

        if (path === 'edit') {
            url = `admin/api/socials/${form.id}`;
            method = 'PUT';
        }

        if (ev.target.checkValidity()) {
            sendRequest(
                {
                    ...form,
                    ...{
                        settings: ace.edit($form.find('.editor-ace')[0]).getValue(),
                    },
                },
                url,
                method,
            );
        }
    });
});
