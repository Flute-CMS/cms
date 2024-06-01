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

    if (typeof selectedGatewayInit !== 'undefined') {
        selectedGateway = selectedGatewayInit;
        checkForValidity();
        updateSubmitButtonState();
    }

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

    promoInput.on('keydown', function search(e) {
        if (e.keyCode == 13) {
            if (promoButton.hasClass('delete-promo-btn')) return;
            applyPromo($(this).val());
        }
    });

    promoInput.on('input', function () {
        updateMessage('', '');
        promoApplied = false;
        togglePromoButtonState(false);
        updateSubmitButtonState();
    });

    promoButton.on('click', function () {
        const promoCode = promoInput.val();
        if (promoButton.hasClass('delete-promo-btn')) return;

        applyPromo(promoCode);
    });

    function applyPromo(promo) {
        $.ajax({
            url: u('api/lk/validate-promo'),
            type: 'POST',
            data: { promo: promo },
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
                console.error('Error:', error);
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
    }

    $(document).on('click', '.delete-promo-btn', function () {
        deletePromo();
    });

    function deletePromo() {
        promoInput.val('');
        updateMessage('', '');
        promoApplied = false;
        promoType = null;
        promoValue = 0;
        togglePromoButtonState(false);
        updateSubmitButtonState();
    }

    $('.select-dropdown li').on('click', function () {
        let currencyCode = $(this).find('input').attr('id');
        selectedCurrency = currencyCode;
        selectedGateway = null;
        $('.lk-result').addClass('inactive');
        $('[data-selectgateway]').removeClass('active');
        updateGatewayVisibility();
        updateFinalAmount();
        deletePromo();
    });

    function updateGatewayVisibility() {
        $('.gateway').each(function () {
            let gatewayKey = $(this).data('selectgateway');
            if (currencyGateways[selectedCurrency].includes(gatewayKey)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }

    function updateFinalAmount() {
        let amount = parseFloat(amountInput.val());
        if (isNaN(amount)) amount = 0;

        let amountInSelectedCurrency =
            amount * (currencyExchangeRates[selectedCurrency] || 1);

        $('#amount_result span').text(
            amountInSelectedCurrency.toFixed(2) + ' ' + selectedCurrency,
        );

        let amountToPay = amount;

        if (promoType && promoValue) {
            switch (promoType) {
                case 'amount':
                    amountToPay = Math.max(0, amount + promoValue); // Deduct promo value from amount to pay
                    break;
                case 'percentage':
                    amountInSelectedCurrency =
                        amount *
                        (1 - promoValue / 100) *
                        (currencyExchangeRates[selectedCurrency] || 1);
                    break;
                case 'subtract':
                    amountToPay = Math.max(
                        getMinimumSum(),
                        amount - promoValue,
                    );
                    break;
            }
        }

        $('#amount_to_pay').addClass('show');

        if (!promoType) $('#amount_result').removeClass('show');
        else $('#amount_result').addClass('show');

        // Display the original amount and the final amount to pay
        $('#amount_result span').text(
            amountToPay.toFixed(2) + ' ' + $('#amount_result').data('currency'),
        );
        $('#amount_to_pay span').text(
            amountInSelectedCurrency.toFixed(2) + ' ' + selectedCurrency,
        );
    }

    function getMinimumSum() {
        return (
            currencyMinimumAmounts[selectedCurrency] /
            (currencyExchangeRates[selectedCurrency] || 1)
        );
    }

    function updateSubmitButtonState() {
        const amountIsValid = isAmountValid();
        const agreeIsChecked =
            agreeCheckbox.length == 0 ? true : agreeCheckbox.is(':checked');
        let enableButton = selectedGateway && amountIsValid && agreeIsChecked;

        if (promoType === 'subtract' && promoValue > 0) {
            let requiredAmount = getMinimumSum() + promoValue;
            if (parseFloat(amountInput.val()) < requiredAmount) {
                updateMessage(
                    'error',
                    translate('lk.min_amount', {
                        ':sum': requiredAmount.toFixed(2),
                    }),
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

        let minSum = getMinimumSum();

        if (promoType === 'subtract' && promoValue > 0) {
            return amount >= promoValue + minSum;
        } else {
            if (amount < minSum)
                updateMessage(
                    'error',
                    translate('lk.min_amount', {
                        ':sum': minSum.toFixed(2),
                    }),
                    'amount',
                );

            return amount >= minSum;
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
        if (!IS_NEW_WINDOW) return;

        document.getElementById('paymentOverlay').classList.add('show');

        e.preventDefault();

        if (!isAmountValid() || !selectedGateway) {
            updateMessage('error', translate('def.check_input'), 'amount');
            return;
        }

        const amount = parseFloat(amountInput.val());
        const promoCode = promoApplied ? promoInput.val() : '';
        const currency = selectedCurrency;

        const url = new URL(u('api/lk/buy/' + selectedGateway));
        url.searchParams.append('amount', amount);
        if (promoCode) url.searchParams.append('promo', promoCode);
        url.searchParams.append('currency', currency);

        let formWindow = createPopupWin(
            url.toString(),
            'Payment page',
            1000,
            600,
        );
        if (formWindow) {
            document
                .getElementById('paymentOverlay')
                .classList.remove('success', 'error');
            document.getElementById('paymentOverlay').classList.add('show');

            formWindow.focus();

            let checkWindowClosed = setInterval(function () {
                if (formWindow.closed) {
                    clearInterval(checkWindowClosed);
                    document
                        .getElementById('paymentOverlay')
                        .classList.remove('show');
                }
            }, 500);

            window.onmessage = function (event) {
                if (event.origin !== window.location.origin) {
                    console.warn(
                        'Received message from unknown origin:',
                        event.origin,
                    );
                    return;
                }

                if (event.data.paymentStatus === 'success') {
                    clearInterval(checkWindowClosed);

                    document
                        .getElementById('paymentOverlay')
                        .classList.add('success');

                    setTimeout(() => {
                        document
                            .getElementById('paymentOverlay')
                            .classList.remove('show');
                        window.location = SITE_URL;
                    }, 3000);
                } else if (event.data.paymentStatus === 'error') {
                    clearInterval(checkWindowClosed);

                    document
                        .getElementById('paymentOverlay')
                        .classList.add('error');

                    setTimeout(() => {
                        document
                            .getElementById('paymentOverlay')
                            .classList.remove('show');
                        window.location = SITE_URL;
                    }, 3000);
                }
            };
        } else {
            toast({
                type: 'error',
                message: translate('def.popup_block_error'),
            });
        }
    });

    function createPopupWin(pageURL, pageTitle, popupWinWidth, popupWinHeight) {
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

    $('.lk-result-content').on('submit', function (e) {
        if (!isAmountValid() || !selectedGateway) {
            // Выведите сообщение об ошибке, если требуется
            e.preventDefault();
            return;
        }

        // Изменение action формы
        $(this).attr('action', u('api/lk/buy/' + selectedGateway));

        // Добавление или обновление скрытого поля для валюты
        updateHiddenInput(this, 'currency', selectedCurrency);

        // Добавление или обновление скрытого поля для промокода
        if (promoApplied) {
            updateHiddenInput(this, 'promo', promoInput.val());
        }
    });

    function updateHiddenInput(form, name, value) {
        let input = $(form).find('input[type="hidden"][name="' + name + '"]');
        if (input.length === 0) {
            $(form).append(
                $('<input>').attr({
                    type: 'hidden',
                    name: name,
                    value: value,
                }),
            );
        } else {
            input.val(value);
        }
    }
});
