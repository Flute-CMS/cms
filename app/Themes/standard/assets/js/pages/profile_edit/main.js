$(document).ready(function () {
    $('.privacy_container_buttons > button').click(function () {
        let hasClass = $(this).hasClass('active');

        $('.privacy_container_buttons > button').removeClass('active');
        $(this).addClass('active');
        const activeButtonIndex = $(this).index();
        $('.background').css('left', `${activeButtonIndex * 50}%`);

        !hasClass &&
            $.post(u(`profile/edit/hidden`), {
                value: activeButtonIndex === 1,
            });
    });

    $('#upload_avatar, #upload_banner').on('change', function () {
        const fileInput = $(this);
        const fileType = fileInput.data('type'); // avatar или banner
        const file = fileInput[0].files[0];

        if (!file) return;

        const formData = new FormData();
        formData.append(fileType, file); // Имя параметра будет либо 'avatar', либо 'banner'

        $.ajax({
            url: u(`profile/edit/${fileType}`), // URL будет либо 'profile/avatar', либо 'profile/banner'
            type: 'POST',
            data: formData,
            processData: false,
            success: function (data) {
                let image = $(`.setting_${fileType} > img`);
                image.attr('src', data.success);
                image.css('display', 'block');

                fileType === 'avatar' &&
                    $('.mini_avatar').attr('src', data.success);

                $(`.setting_${fileType} > .icon_empty`).hide();
                $(`.setting_${fileType} > .overlay`).show();
                // toast({
                //     message: `${fileType.charAt(0).toUpperCase() + fileType.slice(1)} uploaded successfully!`,
                //     type: "success",
                // });
            },
            error: function (jqXHR) {
                try {
                    let error = JSON.parse(jqXHR.responseText);
                    toast({
                        message: error?.error?.message ?? error?.error,
                        type: 'error',
                    });
                } catch (error) {
                    toast({
                        message: 'Something went wrong..',
                        type: 'error',
                    });
                }
            },
        });
    });

    $('.profile_label').each(function () {
        let dataInput = $(this).data('input');

        let $container = $(this).find('.input-container');
        let $button = $(this).find('.save-button');
        let $input = $(this).find("input[type='text']");
        let $textError = $('<div class="input-error"></div>').insertAfter(
            $container,
        ); // Элемент для ошибки

        $input.on('input', function () {
            isSaved = false;
            $button.addClass('active');
            $container.addClass('active').removeClass('error success');
            $textError.hide(); // Скрыть сообщение об ошибке при изменении значения
        });

        $button.on('click', function (e) {
            e.preventDefault();

            if (!$(this).hasClass('active')) return;

            $textError.hide(); // Скрыть сообщение об ошибке перед отправкой запроса

            $.post(
                u(`profile/edit/${dataInput}`),
                {
                    value: $input.val(),
                },
                function (data) {
                    $container.addClass('success').removeClass('active error');
                    isSaved = true;
                    $button.removeClass('active');
                },
            ).fail(function (jqXHR) {
                try {
                    let error = JSON.parse(jqXHR.responseText);
                    $container.addClass('error').removeClass('active success');
                    $textError
                        .text(error?.error?.message ?? error?.error)
                        .show(); // Отображение сообщения об ошибке
                } catch (error) {
                    $container.addClass('error').removeClass('active success');
                    $textError.text('Something wrong..').show(); // Отображение сообщения об ошибке
                }
            });
        });
    });
});

document.addEventListener('click', function (event) {
    if (event.target.hasAttribute('data-delete')) {
        const el = event.target;
        const value = el.getAttribute('data-delete');

        if (value !== 'avatar' && value !== 'banner') return;

        // Если галочка уже есть, выходим из функции
        if (el.dataset.confirmed) return;

        // Создаем иконку галочки
        const confirmIcon = document.createElement('span');
        confirmIcon.className = 'confirm-icon';
        confirmIcon.innerHTML = '<i class="ph ph-check"></i>';

        // Добавляем обработчик события клика на галочку
        confirmIcon.addEventListener('click', function () {
            el.dataset.confirmed = true;
            deleteItem(el, value);

            if( value === 'avatar' )
                $('.mini_avatar').attr('src', u('assets/img/no_avatar.webp'));

            return;
        });

        // Добавляем галочку рядом с элементом
        el.parentNode.insertBefore(confirmIcon, el.nextSibling);
    } else if (!event.target.matches('.confirm-icon, .confirm-icon *')) {
        // Find any open confirm icons and remove them
        var confirmIcons = document.querySelectorAll('.confirm-icon');
        confirmIcons.forEach(function (icon) {
            let parent = icon.parentElement.querySelector(
                '[data-delete]',
            );
            
            parent.removeAttribute('data-confirmed')

            icon.remove();
        });
    }
});

function deleteItem(el, value) {
    const url = u(`profile/${value}`);

    fetch(url, {
        method: 'DELETE',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
        .then((response) => response.json())
        .then((data) => {
            // toast({
            //     message: data.message,
            //     type: "success",
            // });

            const parentEl = el.parentElement;
            parentEl.querySelector('img').style.display = 'none';
            parentEl.querySelector('.icon_empty').style.display = 'block';
            parentEl.querySelector('.overlay').style.display = 'none';
            parentEl.querySelector('.confirm-icon').remove(); // Удаляем иконку галочки
        })
        .catch((error) => {
            let errorMessage = 'Something went wrong..';
            try {
                errorMessage = error?.error?.message ?? error?.error;
            } catch (e) {}

            toast({
                message: errorMessage,
                type: 'error',
            });
        });
}
