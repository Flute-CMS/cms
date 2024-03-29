$(document).ready(function () {
    var debounceTimer;
    const errorMessage = $('#errorMessage');

    let hasError = false;

    $('#route').on('keyup', function () {
        let el = $(this);

        let route = el.val();

        const parent = el.parent().parent();

        clearTimeout(debounceTimer);
        errorMessage.html('');

        parent.removeClass('has-error').removeClass('success');
        debounceTimer = setTimeout(function () {
            $.ajax({
                url: u('admin/api/pages/checkroute'),
                type: 'POST',
                data: {
                    route: route,
                    id: $('input[name="id"]').val(),
                    "x-csrf-token":$('meta[name="csrf-token"]').attr('content'),
                },
                success: function (response) {
                    parent.addClass('success');
                    el.attr('aria-invalid', false);
                    errorMessage.html('');
                    hasError = false;
                },
                error: function (response) {
                    parent.addClass('has-error');
                    el.attr('aria-invalid', true);
                    hasError = true;

                    errorMessage.html(response?.responseJSON?.error);
                },
            });
        }, 500);
    });

    // Проверка при изменении значения
    $('#og_image').change(function () {
        var url = $(this).val();
        if (!isValidUrl(url)) {
            alert('Invalid URL format!');
            $(this).val('');
        }
    });

    // Переключатель доступа
    $('#permissions')
        .change(function () {
            if ($(this).is(':checked')) {
                $('#permissions_block').show(100);
            } else {
                $('#permissions_block').hide(100);
            }
        })
        .change();

    $('form').on('submit', async (e) => {
        e.preventDefault();

        if( hasError ) return;

        let $form = $(e.currentTarget);
        
        let path = $form.data('pagesform'),
            form = serializeForm($form),
            page = $form.data('page'),
            id = $form.data('id');
    
        let url = `admin/api/${page}/${path}`,
            method = 'POST';
    
        if (path === 'edit') {
            url = `admin/api/${page}/${id}`;
            method = 'PUT';
        }
    
        const save = await editor.save();
    
        form['blocks'] = JSON.stringify(save.blocks);
    
        if (e.target.checkValidity()) {
            sendRequest(form, url, method);
        }
    })
});
