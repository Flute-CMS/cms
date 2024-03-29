let selectedCMS = null;
const button = document.getElementById('next');

$('#next').click(() => $('.migrations').toggleClass('next'))

$('.cms').click((e) => {
    $('.cms').not(e.currentTarget).removeClass('active');
    $(e.currentTarget).toggleClass('active');

    selectedCMS = $(e.currentTarget).hasClass('active') ? $(e.currentTarget).data('id') : null;

    checkForBtn();
})

function checkForBtn() {
    button.disabled = selectedCMS ? false : true;
}