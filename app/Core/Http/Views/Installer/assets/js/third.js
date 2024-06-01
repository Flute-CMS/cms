$(function() {
    let form = document.getElementById('form');
    let button = document.querySelector('.check_data');
    let buttonSend = document.querySelector('#continue');

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        // Добавляем к кнопке атрибуты aria-busy и disabled
        button.setAttribute('aria-busy', 'true');
        button.disabled = true;

        // Формируем данные для отправки
        let formData = new FormData(form);

        // Отправляем POST запрос
        fetch(u('install/3'), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(Object.fromEntries(formData))
        })
            .then(async response => {
                if (!response.ok) {
                    return response.json().then(err => { throw err; });
                }
                return response.json();
            })
            .then(function (response) {
                // Обработка ответа от сервера
                if (typeof response?.success !== 'undefined') {
                    button.innerHTML = `${button.getAttribute('data-correct')} <i class="ph-bold ph-check"></i>`;
                    button.disabled = false;
                    buttonSend.disabled = false;
                } else {
                    button.textContent = 'Данные неверны';
                    button.disabled = false;
                }
            })
            .catch(error => {
                // Обработка ошибки
                button.setAttribute('aria-busy', 'false');
                button.disabled = false;
                button.innerHTML = button.getAttribute('data-default');

                let errorMessage = "An error occurred";
                if (error && error.error) {
                    errorMessage = error.error;
                }

                buttonSend.disabled = true;
                addError(errorMessage);
            })
            .finally(function () {
                button.setAttribute('aria-busy', 'false');
                button.disabled = false;
            });
    });
});
