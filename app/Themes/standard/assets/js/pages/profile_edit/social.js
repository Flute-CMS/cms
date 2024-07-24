$(function () {
    var listenerAdded = false;

    function createSocialWind(
        pageURL,
        pageTitle,
        popupWinWidth,
        popupWinHeight,
    ) {
        let left = (screen.width - popupWinWidth) / 2;
        let top = (screen.height - popupWinHeight) / 4;

        let myWindow = window.open(
            pageURL,
            pageTitle,
            'toolbar=no, location=no, directories=no, status=no, menubar=no, resizable=yes, width=' +
                popupWinWidth +
                ', height=' +
                popupWinHeight +
                ', top=' +
                top +
                ', left=' +
                left,
        );

        return myWindow;
    }

    $('[data-connect]').on('click', function (e) {
        e.preventDefault();
        let url = $(this).data('connect');
        let newWindow = createSocialWind(url, 'Social bind', 1000, 800);

        if (!listenerAdded) {
            // Проверка сообщения от нового окна
            window.addEventListener('message', function (event) {
                if (event.data === 'authorization_success') {
                    location.reload(); // Обновление страницы
                } else if (
                    event?.data &&
                    typeof event?.data === 'string' &&
                    event.data.startsWith('authorization_error')
                ) {
                    let errorMessage = event.data.split(':')[1];
                    toast({
                        message: errorMessage,
                        type: 'error',
                        duration: 10000,
                    });
                }
            });

            // Устанавливаем флаг в true, чтобы больше не добавлять обработчик
            listenerAdded = true;
        }
    });

    // Обработчик отправки POST-запроса для показа или скрытия социальных сетей
    // Вешаем обработчик клика на все элементы с атрибутами data-show и data-hide
    document
        .querySelectorAll('[data-show], [data-hide]')
        .forEach(function (element) {
            element.addEventListener('click', function () {
                let action = this.getAttribute('data-show') ? 'show' : 'hide';
                let key = this.getAttribute('data-' + action);
                let endpoint = `profile/social/hide/${key}`;

                // Отправляем POST-запрос
                fetch(u(endpoint), {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'x-csrf-token': csrfToken,
                    },
                    body: JSON.stringify({ key: key, show: action === 'show' }),
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (typeof data.success !== 'undefined') {
                            toggleIcon(this, action);
                        }
                    });
            });
        });

    function toggleIcon(element, currentAction) {
        let newAction = currentAction === 'show' ? 'hide' : 'show';
        let key = element.getAttribute('data-' + currentAction);

        if (currentAction === 'show') {
            element.classList.remove('ph', 'ph-eye-slash');
            element.classList.add('ph', 'ph-eye');
            element.removeAttribute('data-show');
            element.setAttribute('data-hide', key);
        } else {
            element.classList.remove('ph', 'ph-eye');
            element.classList.add('ph', 'ph-eye-slash');
            element.removeAttribute('data-hide');
            element.setAttribute('data-show', key);
        }
    }

    // Обработчик отключения
    $('[data-disconnect]').on('click', function (e) {});
});
