$("#cookie_close").on('click', (e) => {
    setCookie('accept_cookie', 'true', 365);
    let element = $('.toast-cookie');
    element.removeClass('show');
    setTimeout(() => element.remove(), 300);
});