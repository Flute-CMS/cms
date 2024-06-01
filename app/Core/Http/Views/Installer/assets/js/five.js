$(function() {
    let form = document.getElementById('form');
    let button = document.getElementById('continue');
    let inputs = form.querySelectorAll('input, select, textarea');

    // Функция для проверки валидности всех полей формы
    let checkFormValidity = function () {
        let allValid = true;
        inputs.forEach(function (input) {
            if (!input.checkValidity()) {
                allValid = false;
            }
        });
        button.disabled = !allValid;
    };

    // Проверка валидности формы при изменении каждого поля
    inputs.forEach(function (input) {
        input.addEventListener('input', checkFormValidity);
    });

    // Первоначальная проверка валидности формы
    checkFormValidity();
});
