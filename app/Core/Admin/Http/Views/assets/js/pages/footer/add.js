$(function() {
    // Функция проверки валидности URL или относительного пути
    function isValidPathOrUrl(string) {
        // Проверка на валидность полного URL
        try {
            new URL(string);
            return true;
        } catch (_) {
            // Проверка на соответствие формату относительного пути
            const relativePathPattern =
                /^\/[A-Za-z0-9\-._~:\/?#\[\]@!$&'()*+,;=]*$/;
            return relativePathPattern.test(string);
        }
    }

    // Обработчик события изменения текста в поле URL
    $(document).on('input', '#url', function () {
        var pathOrUrl = $(this).val();
        if (isValidPathOrUrl(pathOrUrl)) {
            $('#new_tab').closest('.form-group').fadeIn(300);
        } else {
            $('#new_tab').prop('checked', false);
            $('#new_tab').closest('.form-group').fadeOut(300);
        }
    });

    $(document).on('submit', '#footAdd, #footEdit', (ev) => {
        let $form = $(ev.currentTarget);
    
        ev.preventDefault();
        
        let path = $form.attr('id') === 'footAdd' ? 'add' : 'edit', form = serializeForm($form);

        let url = `admin/api/footer/${path}`,
            method = 'POST';
    
        if (path === 'edit') {
            url = `admin/api/footer/${form.id}`;
            method = 'PUT';
        }
    
        if (ev.target.checkValidity()) {
            sendRequest(form, url, method);
        }
    });
});
