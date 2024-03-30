$(document).ready(() => {
    let selectedGateway = null;
    let promoApplied = false;
    let promoType = null;
    let promoValue = 0;
    const promoInput = $('#promo');
    const promoButton = $('.btn-absolute');
    const amountInput = $('#amount');
    const agreeCheckbox = $('#agree');
    const submitButton = $('#buy_btn');
    const messageContainer = $('#messagePromo');
    const messageAmount = $('#messageAmount');

    function updateMessage(type, message, container = 'promo') {
        const icon =
            message === ''
                ? ''
                : type === 'success'
                ? '<i class="ph ph-check-circle"></i>'
                : '<i class="ph ph-x-circle"></i>';

        if (container === 'promo')
            messageContainer
                .removeClass('success error')
                .addClass(type)
                .html(icon + message);
        else {
            amountInput.attr(
                'aria-invalid',
                message.length > 0 || amountInput.val().length === 0,
            );

            messageAmount
                .removeClass('success error')
                .addClass(type)
                .html(icon + message);
        }
    }

    function togglePromoButtonState(isApplied) {
        const buttonText = translate(isApplied ? 'def.delete' : 'def.accept');
        promoButton
            .html(buttonText)
            .toggleClass('delete-promo-btn', isApplied)
            .toggleClass('show', promoInput.val().length > 0);
    }

    $('[data-selectgateway]').on('click', (e) => {
        let el = $(e.currentTarget);

        if (el.hasClass('active')) return;

        $('[data-selectgateway]').not(el).removeClass('active');
        el.toggleClass('active');

        selectedGateway = el.hasClass('active')
            ? el.data('selectgateway')
            : null;

        checkForValidity();
        updateSubmitButtonState();
    });

    function checkForValidity() {
        $('.lk-header').addClass('active');
        $('.lk-gateways > .lk-header').removeClass('active');
        $('.lk-result').removeClass('inactive');
    }

    promoInput.on('input', function () {
        updateMessage('', '');
        promoApplied = false;
        togglePromoButtonState(false);
        updateSubmitButtonState();
    });

    promoButton.on('click', function () {
        const promoCode = promoInput.val();

        if (promoButton.hasClass('delete-promo-btn')) return;

        $.ajax({
            url: u('api/lk/validate-promo'),
            type: 'POST',
            data: { promo: promoCode },
            success: function (response) {
                if (response.success) {
                    promoType = response.success.type;
                    promoValue = response.success.value;
                } else {
                    promoType = null;
                    promoValue = 0;
                }

                updateMessage(
                    response.success ? 'success' : 'error',
                    response.success.message || translate('def.unknown_error'),
                );
                promoApplied = response.success;
                togglePromoButtonState(response.success);
                updateSubmitButtonState();
            },
            error: function (error) {
                console.error('Ошибка:', error);
                promoType = null;
                promoValue = 0;
                updateMessage(
                    'error',
                    error?.responseJSON?.error ||
                        translate('def.unknown_error'),
                );
                promoApplied = false;
                updateSubmitButtonState();
            },
        });
    });

    $(document).on('click', '.delete-promo-btn', function () {
        promoInput.val('');
        updateMessage('', '');
        promoApplied = false;
        promoType = null;
        promoValue = 0;
        togglePromoButtonState(false);
        updateSubmitButtonState();
    });

    function updateFinalAmount() {
        let amount = parseFloat(amountInput.val());
        if (isNaN(amount)) amount = 0;

        let amountToPay = amount;
        if (promoType && promoValue) {
            switch (promoType) {
                case 'amount':
                    amount = Math.max(0, amount + promoValue); // Deduct promo value from amount to pay
                    break;
                case 'percentage':
                    amountToPay = amount * (1 - promoValue / 100); // Deduct a percentage from amount to pay
                    break;
                case 'subtract':
                    amountToPay = Math.max(MIN_AMOUNT, amount - promoValue);
                    break;
            }
        }

        $('#amount_to_pay').addClass('show');

        if (!promoType) $('#amount_result').removeClass('show');
        else $('#amount_result').addClass('show');

        // Display the original amount and the final amount to pay
        $('#amount_result span').text(
            amount.toFixed(2) + ' ' + $('#amount_result').data('currency'),
        );
        $('#amount_to_pay span').text(
            amountToPay.toFixed(2) + ' ' + $('#amount_to_pay').data('currency'),
        );
    }

    function updateSubmitButtonState() {
        const amountIsValid = isAmountValid();
        const agreeIsChecked =
            agreeCheckbox.length == 0 ? true : agreeCheckbox.is(':checked');
        let enableButton = selectedGateway && amountIsValid && agreeIsChecked;

        console.log(promoType, promoValue);

        if (promoType === 'subtract' && promoValue > 0) {
            const requiredAmount = promoValue + MIN_AMOUNT;
            if (parseFloat(amountInput.val()) < requiredAmount) {
                updateMessage(
                    'error',
                    translate('lk.min_amount', { ':sum': requiredAmount }),
                );
                enableButton = false;
                promoApplied = false;
                promoType = null;
                togglePromoButtonState(false);
            }
        }

        submitButton.prop('disabled', !enableButton);
        promoInput.prop('disabled', !amountIsValid || promoApplied);
        updateFinalAmount();
    }

    function isAmountValid() {
        let amount = parseFloat(amountInput.val());
        if (isNaN(amount)) return false;

        if (promoType === 'subtract' && promoValue > 0) {
            return amount >= promoValue + MIN_AMOUNT;
        } else {
            if (amount < MIN_AMOUNT)
                updateMessage(
                    'error',
                    translate('lk.min_amount', { ':sum': MIN_AMOUNT }),
                    'amount',
                );

            return amount >= MIN_AMOUNT;
        }
    }

    amountInput.on('input', function () {
        updateMessage('', '');
        updateMessage('', '', 'amount');
        promoApplied = false;
        togglePromoButtonState(false);
        updateSubmitButtonState();
    });

    agreeCheckbox.on('change', updateSubmitButtonState);

    submitButton.on('click', function (e) {
        e.preventDefault(); // Предотвратить стандартное поведение кнопки

        if (!isAmountValid() || !selectedGateway) {
            // updateMessage('error', 'Пожалуйста, проверьте вводимые данные', 'amount');
            return;
        }

        const amount = parseFloat(amountInput.val());
        const promoCode = promoApplied ? promoInput.val() : '';

        $.ajax({
            url: u('api/lk/buy/' + selectedGateway),
            type: 'POST',
            data: {
                amount: amount,
                promo: promoCode,
            },
            success: function (response) {
                if (response.link) {
                    window.location.href = response.link;
                } else {
                    toast({
                        type: 'error',
                        message:
                            response?.error || translate('def.unknown_error'),
                    });
                }
            },
            error: function (error) {
                console.error('Ошибка:', error);
                toast({
                    type: 'error',
                    message:
                        error?.responseJSON?.error ||
                        translate('def.unknown_error'),
                });
            },
        });
    });
});
