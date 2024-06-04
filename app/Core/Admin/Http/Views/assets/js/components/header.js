function showSearchResults() {
    $('#searchResults').addClass('show').focus();
    $('#searchBg').addClass('show');
}

function hideSearchResults() {
    $('#searchBg').removeClass('show');
    $('#adminSearch').val('');
    $('#searchResults').empty().removeClass('show');
}

$(document).on('click', '#searchBg', () => hideSearchResults());

$(document).on(
    'input',
    '#adminSearch',
    debounce(function () {
        let searchValue = $(this).val();
        $('#searchBg').addClass('show');
        if (searchValue.length >= 3) {
            $.ajax({
                url: '/admin/api/search/' + encodeURIComponent(searchValue),
                type: 'GET',
                success: function (data) {
                    $('#searchResults').empty();

                    if (data.length) {
                        data.forEach((item) => {
                            let highlightedTitle = item.title.replace(
                                new RegExp(searchValue, 'gi'),
                                (match) => `<b>${match}</b>`,
                            );
                            let category = item.category
                                ? `<p class="result-category">${item.category}</p>`
                                : '';
                            $('#searchResults').append(
                                `<a href="${item.url}" class="search-result-link" data-search-icon="${item.icon}">
                                    ${highlightedTitle}
                                    ${category}
                                </a>`,
                            );
                        });
                        showSearchResults();
                    } else {
                        // hideSearchResults();
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error: ' + error);
                    hideSearchResults();
                },
            });
        } else {
            $('#searchResults').empty();
        }
    }, 150),
);

$(document).on('click', '.search-result-link', function (event) {
    event.preventDefault();
    let link = event.currentTarget;
    let icon = link.getAttribute('data-search-icon');

    fetchContentAndAddTab(
        new URL(link.href).pathname,
        link.textContent.trim(),
        icon.length > 0 ? `<i class="ph ${icon}"></i>` : '',
    );
    hideSearchResults();
    $('#adminSearch').blur();
});

$(document).on('keydown', function (e) {
    if (e.ctrlKey && e.keyCode === 75) {
        e.preventDefault();
        $('#searchBg').addClass('show');
        $('#adminSearch').focus();
    } else if (e.key === 'Escape') {
        hideSearchResults();
        $('#adminSearch').blur();
    } else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        let current = $('#searchResults .search-result-link.highlight');
        if (current.length === 0) {
            if (e.key === 'ArrowDown') {
                let first = $('#searchResults .search-result-link')
                    .first()
                    .addClass('highlight');
                first[0].scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                });
            }
        } else {
            let next =
                e.key === 'ArrowDown'
                    ? current.next('.search-result-link')
                    : current.prev('.search-result-link');
            if (next.length) {
                current.removeClass('highlight');
                next.addClass('highlight');
                next[0].scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                });
            }
        }
    } else if (e.key === 'Enter') {
        let highlighted = $('#searchResults .search-result-link.highlight');
        if (highlighted.length) {
            highlighted[0].click();
        }
    }
});

$(document).on('click', '.update-btn', function () {
    var versionElement = $('.version');
    versionElement.attr('aria-busy', 'true');

    $.ajax({
        url: u('admin/api/check-update'),
        method: 'GET',
        dataType: 'json',
        success: function (response) {
            if (response.need) {
                $('.update-btn').remove();
                var updateMessage = response.need;
                var updateButton = $('<a>', {
                    href: u('admin/update'),
                    class: 'gradient-text',
                    id: 'updateBtn',
                    'data-tab': true,
                    html: '<i class="ph ph-confetti"></i> ' + updateMessage,
                });
                $('.header_version').append(updateButton);
                toast({
                    message: translate('admin.update.new_update'),
                    type: 'success',
                });
            } else {
                toast({
                    message: translate('admin.update.no_updates'),
                });
            }
            versionElement.attr('aria-busy', 'false');
        },
        error: function () {
            versionElement.attr('aria-busy', 'false');
            console.error('Error checking update');
        },
    });
});
