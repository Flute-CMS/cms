$(document).on('click', '[data-lookblocks]', () => {
    $('.bans-modal').addClass('opened');
});

$(document).on('click', '.bans-modal-card-header-close', () => {
    $('.bans-modal').removeClass('opened');
});

$(document).on('click', '.bans-modal', (e) => {
    if (e.currentTarget !== e.target) return;

    $('.bans-modal').removeClass('opened');
});
