const buttonNext = document.querySelector('#continue');
const currentStep = $("[data-step]").data('step');
const currentFinish = $("[data-finish]").data('finish');

document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('.logo-container').classList.add('start-animation');
});

$(buttonNext).on('click', () => {
    let form = document.getElementById('form');

    let formData = form ? new FormData(form) : {};

    buttonNext.setAttribute('disabled', true);
    buttonNext.setAttribute('aria-busy', true);
    clearErrors();

    fetch(u(`install/${currentStep}`), {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        body: form ? JSON.stringify(Object.fromEntries(formData)) : {}
    })
        .then(async response => {
            if (!response.ok) {
                return response.json().then(err => { throw err; });
            }
            return response.json();
        })
        .then(data => {
            buttonNext.removeAttribute('aria-busy');
            buttonNext.classList.add('success');
            buttonNext.innerHTML = `<i class="ph ph-check"></i>`;
            buttonNext.setAttribute('disabled', true);
            timerToRedirect();

            if (currentFinish == 1) {
                startConfetti();
            }
        })
        .catch(error => {
            // Обработка ошибки
            buttonNext.removeAttribute('aria-busy');
            buttonNext.removeAttribute('disabled');

            let errorMessage = "An error occurred";
            if (error && error.error) {
                errorMessage = error.error;
            }

            addError(errorMessage);
        });
})

$('.errors_background').click(() => clearErrors());

let count = 200;
let defaults = {
    origin: { y: 0.7 }
};

function fire(particleRatio, opts) {
    confetti(Object.assign({}, defaults, opts, {
        particleCount: Math.floor(count * particleRatio)
    }));
}

function startConfetti() {
    fire(0.25, {
        spread: 26,
        startVelocity: 55,
    });
    fire(0.2, {
        spread: 60,
    });
    fire(0.35, {
        spread: 100,
        decay: 0.91,
        scalar: 0.8
    });
    fire(0.1, {
        spread: 120,
        startVelocity: 25,
        decay: 0.92,
        scalar: 1.2
    });
    fire(0.1, {
        spread: 120,
        startVelocity: 45,
    });
}

function clearErrors() {
    $('#errors_container').hide();
    $('.errors_background').hide();
    $('#errors_container .error').remove();
}

function addError(message) {
    $('.errors_background').show();
    $('#errors_container').show().addClass('animate__animated').addClass('animate__fadeIn').append(`<div class="error">
        <div class="error_text">
            ${message}
        </div>
    </div>`);

    // <h3>SERVER ERROR:</h3>
}

function timerToRedirect() {
    setTimeout(function () {
        window.location.href = u(currentFinish == 1 ? '' : `install/${currentStep + 1}`);
    }, 1500);
}

function showErrors(errors, focus = true) {
    errors.forEach((error) => {
        if (error.message) {
            const element = $(error.element);
            if (element.prop('type') !== 'submit') { // Проверка типа элемента
                element.attr('aria-invalid', 'true'); // Добавление aria-invalid="true"
            }
            element.closest('.input-form').addClass('has-error').find('.error').remove();
            $('<span class="error">').text(error.message).insertAfter(element);
            if (focus && element.focu) {
                element.focus();
                focus = false;
            }
        }
    })
}

function removeErrors(elem) {
    const element = $(elem);
    if (element.prop('type') !== 'submit') { // Проверка типа элемента
        element.removeAttr('aria-invalid'); // Удаление атрибута aria-invalid
    }
    if (element.is('form')) {
        $('.has-error', elem).removeClass('has-error');
        $('.error', elem).remove()
    } else {
        element.closest('.input-form').removeClass('has-error').find('.error').remove()
    }
}

Nette.showFormErrors = function (form, errors) {
    removeErrors(form);
    showErrors(errors, true)
};

$(function () {
    $(':input[data-nette-rules]').keypress((event) => {
        if ($(event.target).prop('type') !== 'submit') { // Проверка типа элемента
            removeErrors(event.target)
        }
    });
    $(':input[data-nette-rules]').blur((event) => {
        Nette.formErrors = [];
        Nette.validateControl(event.target);
        if (Nette.formErrors.length > 0) {
            showErrors(Nette.formErrors)
        } else {
            if ($(event.target).prop('type') !== 'submit') { // Проверка типа элемента
                $(event.target).attr('aria-invalid', 'false'); // Добавление aria-invalid="false", если ошибок нет
            }
        }
    })

    $('#editMode').change((event) => {
        if ($(event.target).is(':checked')) {
            window.location.href = appendGet(window.location.href, 'editMode', 1);
        } else {
            window.location.href = appendGet(window.location.href, 'editMode', 0);
        }
    });
})