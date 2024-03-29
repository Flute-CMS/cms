const $navigation = $('.navigation');
const $navigationAdditional = $('.additional-menu');
const $openMenu = $('.first-item');
const $closeMenu = $('.close-item');

$(document).ready(() => {
    $openMenu.click(() => $navigation.addClass('opened'))
    $closeMenu.click(() => $navigation.removeClass('opened'))
})