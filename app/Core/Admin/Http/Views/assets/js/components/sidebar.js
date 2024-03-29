$(document).ready(function () {
    if (document.querySelector('.item.opened'))
        document
            .querySelector('.item.opened')
            .scrollIntoView({ behavior: 'smooth' });

    $('.main-menu .item').on('click', function (e) {
        e.preventDefault();
        const itemTitle = $(this).data('title');
        const itemUrl = $(this).data('path');

        if (itemUrl === '#') return;

        addToRecent(itemTitle, itemUrl);
    });

    $('.additional-menu .btn-add-menu a').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation(); // Предотвращаем всплытие события, чтобы не активировать родительский элемент

        const itemTitle = $(this).data('title');
        const itemUrl = $(this).data('path');

        if (itemUrl === '#') return;

        addToRecent(itemTitle, itemUrl);
    });

    $('[data-hide]').on('click', (e) => {
        let el = $(e.currentTarget);

        if ($('.recent-menu > .items').hasClass('hidden')) {
            el.html(el.data('hide'));
            setCookie('recent_hide', 'false', 365);
        } else {
            el.html(el.data('open'));
            setCookie('recent_hide', 'true', 365);
        }

        $('.recent-menu > .items').toggleClass('hidden');
    });

    $('[data-delete]').on('click', (e) => {
        let el = $(e.currentTarget);
        let pathDelete = el.data('delete');

        if (!pathDelete) return;

        deleteRecent(pathDelete);

        el.parent().remove();

        if ($('[data-delete]').length === 0) $('.recent-menu').remove();
    });

    $('.additional-menu > .items > button > .head-button').on('click', (e) => {
        const parent = $(e.currentTarget).parent();
        $('.additional-menu > .items > button')
            .not(parent)
            .removeClass('opened');

        parent.toggleClass('opened');
    });
});

function addToRecent(itemTitle, itemUrl) {
    fetch(u('admin/api/recent'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            title: itemTitle,
            url: itemUrl,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                console.log('Item added to recent');
            } else {
                console.error('Failed to add item');
            }

            window.location.href = itemUrl;
        })
        .catch((error) => console.error('Error:', error));
}

function deleteRecent(itemTitle) {
    fetch(u('admin/api/recent'), {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            title: itemTitle,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                console.log('Item deleted');
            } else {
                console.error('Failed to add item');
            }
        })
        .catch((error) => console.error('Error:', error));
}
