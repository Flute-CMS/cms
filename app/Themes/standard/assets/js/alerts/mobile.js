$("#mobile_close").on('click', (e) => {
    setCookie('mobile_alert', 'true', 365);
    let element = $('.mobile_alert');
    element.removeClass('opened');
    setTimeout(() => element.remove(), 300);
});