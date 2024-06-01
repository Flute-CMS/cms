$(function () {
    $(document).on(
        'input',
        '#currency, #minimum_value, #exchange_rate',
        function () {
            let currencyCode = $('#currency').val();
            let minValue = parseFloat($('#minimum_value').val());
            let exchangeRate = parseFloat($('#exchange_rate').val());

            let adjustedMinValue = minValue / exchangeRate;

            $('#currency_name').html(currencyCode);
            $('#exchange_rate_display').text(
                `1 ${SITE_CURRENCY} = ${exchangeRate.toFixed(
                    3,
                )} ${currencyCode}`
            );
            $('#min_sum').text(
                `${adjustedMinValue.toFixed(2)}`,
            );

            !$("#result-currency").hasClass('show') && $("#result-currency").addClass('show');
        },
    );
});
