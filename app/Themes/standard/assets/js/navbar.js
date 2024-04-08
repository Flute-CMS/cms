const $submenuContainer = $('.submenu_container');
const $notificationsCounter = $('.notifications_counter');
const $backgroundNavbar = $('#background_navbar');
const $openNotifications = $('#openNotifications');
const $openMiniProfile = $('#openMiniProfile');
const $searchInput = $('#search');
const $navbarContainer = $('.navbar--container');
const originalTitle = document.title;
let batchTimeout,
    notificationBatch = [],
    currentAjaxRequest = null;

$(document).ready(async () => {
    initializeEventHandlers();
    updateNotifications();
    setInterval(updateNotifications, 25000);

    $(document).on('click', function (event) {
        if (
            !$(event.target).closest(
                '.navbar--container, #openNotifications, #openMiniProfile, #search, .navbar-search',
            ).length
        ) {
            closeAllHandle();
        }
    });
});

function initializeEventHandlers() {
    $('.standard_icons > li[data-child]').on('click', handleIconClick);
    $backgroundNavbar.on('click', handleBackgroundClick);
    $('#openSearch').on('click', openSearch);
    $openNotifications.on('click', toggleNotifications);
    $('.notifications_header_clear').on('click', clearNavbarNotifications);
    $('.miniprofile_icon').on('click', toggleMiniprofileExpanded);
    $('.miniprofile_lang').on('click', toggleLang);
    $('.miniprofile_langs_item').on('click', checkActiveLang);
    $('.navbar-burger').on('click', toggleBurger);
    $('.nav_guest_lang').on('click', toggleLangContainer);
    $('#closeSearch').on('click', closeSearch);
    $('#search').on('input', debounce(handleSearchInput, 300));
    $openMiniProfile.on('click', toggleMiniprofile);

    $('.navbar--container-items > div').on('click', (e) => {
        let el = $(e.currentTarget);

        $('.navbar--container-items > div').not(el).removeClass('opened');
        el.toggleClass('opened');
    });

    $(document).keydown(function (e) {
        // Проверка на нажатие клавиши Escape
        if (e.key === 'Escape' || e.keyCode === 27) {
            closeAllHandle();
            e.preventDefault();
            return;
        }

        let activeElement = document.activeElement;
        let isContentEditable =
            activeElement &&
            activeElement.getAttribute('contenteditable') === 'true';

        // Проверяем, нажаты ли модификаторы (Ctrl, Alt, Meta)
        if (
            e.key === 'Win' ||
            e.key === 'Meta' ||
            e.key === 'Backspace' ||
            e.shiftKey ||
            e.ctrlKey ||
            e.altKey ||
            e.metaKey
        ) {
            return; // Игнорируем нажатие, если зажата одна из этих клавиш
        }

        if (!$('input').is(':focus') && !isContentEditable && !$('textarea').is(':focus')) {
            // Помещаем фокус на целевой input
            $searchInput.focus();
            $searchInput.parent().parent().addClass('opened');
            $navbarContainer.hide();
            closeNotifications();
            closeMiniProfile();

            // Добавляем символ, если это печатаемый символ (не управляющая клавиша)
            if (e.key.length === 1) {
                // Получаем текущее значение input и добавляем введенный символ
                let currentValue = $searchInput.val();
                $searchInput.val(currentValue + e.key);

                // Обновляем класс родителя
                updateParentClass('has-value');

                $('.search-bg-panel').remove();

                let bg = make('div');
                bg.classList.add('search-bg-panel');
                bg.onclick = () => closeAllHandle();
                document.body.appendChild(bg);

                // Предотвращаем дальнейшую обработку события браузером
                e.preventDefault();
            }
        }
    });

    $searchInput.on('input', function () {
        updateParentClass('has-value');
    });
}

function updateParentClass(className) {
    if ($searchInput.val()) {
        $searchInput.parent().parent().addClass(className);
        $navbarContainer.hide();
    } else {
        $searchInput.parent().parent().removeClass(className);
    }
}

function closeAllHandle() {
    closeAll();
    $navbarContainer.show();
    $('.search-bg-panel').remove();
    $('#search_container').removeClass('show');
    $searchInput.parent().parent().removeClass('opened');
    $('.navbar--container-items > div').removeClass('opened');
}

function clearNavbarNotifications() {
    clearNotifications();
    emptyNotifications();
}

function handleIconClick(e) {
    e.preventDefault();

    let $this = $(this); // Сохраните $(this) в переменной
    let $thisAnchor = $this.find('a');
    let isActive = $thisAnchor.hasClass('active');

    // Удалите класс 'active' со всех элементов
    $('.standard_icons > li[data-child] > a').removeClass('active');

    closeAll();

    // Переключите класс 'active' на текущем элементе
    $thisAnchor.toggleClass('active', !isActive);

    // Очистите .submenu_container перед добавлением новых элементов
    $('.submenu_container').empty();

    if (isActive) {
        closeAll();
    } else {
        $this.find('.submenu li').clone().appendTo('.submenu_container');
        toggleBackground();
    }
}

function toggleBackground() {
    const isOpen = $backgroundNavbar.hasClass('open');
    $backgroundNavbar.add($submenuContainer).toggleClass('open', !isOpen);
    if (isOpen) {
        $submenuContainer.empty();
        closeAll();
    }
}

function handleBackgroundClick(e) {
    closeAll();
}

function clearSearchResults() {
    $('.search_result').remove();
}

function openSearch() {
    closeAll();
    $searchInput.parent().parent().addClass('opened');
    $searchInput.focus();
    let bg = make('div');
    bg.classList.add('search-bg-panel');
    bg.onclick = () => closeAllHandle();
    document.body.appendChild(bg);

    toggleBackground();
}

function handleSearchInput(e) {
    $('#search_container').empty();

    let searchValue = e.target.value;

    // abort the request if it's pending
    if (currentAjaxRequest) {
        currentAjaxRequest.abort();
    }

    if (searchValue.length > 2) {
        currentAjaxRequest = $.ajax({
            type: 'POST',
            url: u('api/search/' + searchValue),
            success: async function (data) {
                let searchResult = await createSearchResult(data);
                $('#search_container').append(searchResult);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                if (textStatus !== 'abort') {
                    console.error(
                        'Error occurred during search: ' + errorThrown,
                    );
                }
            },
        });
        $('#search_container').addClass('show');
    } else {
        clearSearchResults();
    }
}

async function createSearchResult(data) {
    let searchResult = $('<div>', { class: 'search_result' });
    let searchResultTitle = $('<div>', { class: 'search_result_title' });
    let searchResultCount = $('<div>', { id: 'search_result_count' });
    let searchResultItems = $('<div>', { class: 'search_result_items' });

    // Add content depending on whether there are results
    if (data.length > 0) {
        searchResultTitle.html(translate('def.found'));
        searchResultCount.text(data.length);

        data.forEach(function (item) {
            let resultItem = createResultItem(item);
            searchResultItems.append(resultItem);
        });
    } else {
        searchResultTitle.html(translate('def.found'));
        searchResultCount.text('0');

        // Add Font Awesome icon
        // let icon = $('<i>', { class: 'ph ph-warning-circle not-found-icon' });
        // searchResultItems.append(icon);
    }

    searchResultTitle.append(searchResultCount);

    searchResult.append(searchResultTitle, searchResultItems);

    return searchResult;
}

function createResultItem(item) {
    let resultItem = $('<a>', { href: item?.url });
    let resultItemIcon = $('<div>', { class: 'search_result_item_icon' });
    let resultItemText = $('<div>', { class: 'search_result_item_text' });
    let resultItemTextTitle = $('<div>', {
        class: 'search_result_item_text_title',
        text: item.title,
    });
    let resultItemTextText = item?.description
        ? $('<div>', {
              class: 'search_result_item_text_text',
              text: item.description,
          })
        : '';
    let resultItemIconArrow = $('<div>', { class: 'search_result_icon' });

    if (
        (item?.image && item.image.includes('.png')) ||
        item.image.includes('.jpg') ||
        item.image.includes('.gif') ||
        item.image.includes('.jpeg') ||
        item.image.includes('.svg') ||
        item.image.includes('.webp')
    ) {
        resultItemIcon.append('<img loading="lazy" src="' + item.image + '"/>');
    } else if (item?.image) {
        resultItemIcon.append('<i class="' + item.image + '"></i>');
    }

    resultItemText.append(resultItemTextTitle, resultItemTextText);
    resultItemIconArrow.append('<i class="ph ph-arrow-right"></i>');

    resultItem.append(resultItemIcon, resultItemText, resultItemIconArrow);

    return resultItem;
}

function addNotificationCount(count = 1) {
    if (!$(document).find('.notifications_counter').length) {
        let notificationCount = $('<div>', {
            class: 'notifications_counter',
            text: count ?? 1,
        });
        $('#openNotifications').append(notificationCount);
    } else {
        $('.notifications_counter').html(
            count ?? parseInt($('.notifications_counter').text()) + 1,
        );
    }
}

function removeNotificationCount() {
    let count = parseInt($('.notifications_counter').text()) - 1;

    if (count == 0) {
        $('.notifications_counter').remove();
    } else {
        $('.notifications_counter').html(count);
    }
}

function toggleNotifications() {
    if ($('.notifications_container').hasClass('opened')) {
        closeNotifications();
    } else {
        closeAll();
        openNotifications();
    }
}

function readNotificationsBatch() {
    if (batchTimeout) clearTimeout(batchTimeout);

    batchTimeout = setTimeout(function () {
        if ($('.notifications_container').hasClass('opened')) {
            // Преобразуем в обычный массив, затем очищаем batch
            let batch = [...notificationBatch];
            notificationBatch = [];
            for (let i = 0; i < batch.length; i++) {
                readNavbarNotification(batch[i]);
            }
        }
    }, 200); // Задержка в 100 миллисекунд
}

async function selectNotificationPhrase(count) {
    let cases = [2, 0, 1, 1, 1, 2];
    let forms = [
        'new_notification_1',
        'new_notification_2',
        'new_notification_5',
    ];
    return translate(
        `def.${
            forms[
                count % 100 > 4 && count % 100 < 20
                    ? 2
                    : cases[count % 10 < 5 ? count % 10 : 5]
            ]
        }`,
        { num: count },
    );
}

async function updateNotifications() {
    if ($('#openNotifications').length === 0) return;

    let notifications =
        NOTIFICATIONS_MODE && NOTIFICATIONS_MODE === 'all'
            ? getAllNotifications()
            : getNotifications();

    let count = 0;
    for (let date in notifications) {
        let notification = notifications[date];

        // Get or create notification block for this date
        let notificationBlock = $(
            `#notification_block_${date.replace(/[-.]/g, '')}`,
        );
        if (notificationBlock.length === 0) {
            // Block doesn't exist, so create it
            notificationBlock = createNotificationBlock(date);
            $('.notifications_body').prepend(notificationBlock);
        }

        // Get the .notifications_date_flex block
        let notificationsFlex = notificationBlock.find(
            '.notifications_date_flex',
        );

        for (let i = 0; i < notification.length; i++) {
            // Check if notification already exists in the block, if not then append it
            if ($('#notification_' + notification[i].id).length === 0) {
                let notificationItem = await createNotificationItem(
                    notification[i],
                );
                notificationsFlex.prepend(notificationItem);
            }

            if (!notification[i].viewed) {
                count++;
            }
        }
    }

    if (count > 0) {
        addNotificationCount(count);

        // Меняем title страницы на новое сообщение
        let newTitle = await selectNotificationPhrase(count);

        if (window.notificationIntervalId) {
            clearInterval(window.notificationIntervalId);
            window.notificationIntervalId = null;
        }

        // Создаём интервал, который будет возвращать оригинальный title и устанавливать новый каждые 2 секунды
        let intervalId = setInterval(() => {
            document.title =
                document.title === originalTitle ? newTitle : originalTitle;
        }, 2000);

        // Сохраняем id интервала в window, чтобы мы могли остановить его позже
        window.notificationIntervalId = intervalId;

        $('.notifications_header_clear').show();
        $('.notifications_body > i').remove();
    } else {
        $('.notifications_header_clear').hide();

        if ($('.notifications_body > .notifications_items').length === 0)
            emptyNotifications();

        removeNotificationCount();

        // Если у нас есть интервал, который меняет title страницы, мы его останавливаем
        if (window.notificationIntervalId) {
            clearInterval(window.notificationIntervalId);
            window.notificationIntervalId = null;
        }

        // Возвращаем title страницы в исходное состояние
        document.title = originalTitle;
    }

    let observer = new IntersectionObserver(
        (entries, observer) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    let id = entry.target.id.replace('notification_', '');
                    notificationBatch.push(id);
                    readNotificationsBatch();
                    $(`#${entry.target.id}`).removeClass('unread');
                    observer.unobserve(entry.target);
                }
            });
        },
        { root: document.querySelector('.notifications_container') },
    );

    $('.notifications_item.unread').each(function () {
        observer.observe(this);
    });
}

function openNotifications() {
    $('.notifications_container').addClass('opened');

    readNotificationsBatch();

    // Если у нас есть интервал, который меняет title страницы, мы его останавливаем
    if (window.notificationIntervalId) {
        clearInterval(window.notificationIntervalId);
        window.notificationIntervalId = null;
    }

    // Возвращаем title страницы в исходное состояние
    document.title = originalTitle;
}

function emptyNotifications() {
    $('.notifications_body').html('<i class="ph ph-bell-simple-slash"></i>');
}

function createNotificationBlock(date) {
    let notificationBlock = $('<div>', {
        class: 'notifications_items',
        id: `notification_block_${date.replace(/[-.]/g, '')}`,
    });
    let notificationSpan = $('<span>', {
        class: 'notifications_span',
        text: date,
    });
    let notificationsFlex = $('<div>', { class: 'notifications_date_flex' });

    notificationBlock.append(notificationSpan, notificationsFlex);

    return notificationBlock;
}

async function createNotificationItem(notification) {
    let notificationItem = $('<div>', {
        class: 'notifications_item' + (notification.viewed ? '' : ' unread'),
        id: `notification_${notification.id}`,
    });
    let notificationFlex = $('<div>', { class: 'notifications_item_flex' });

    notificationFlex.append(notification.icon);

    let notificationContent = $('<div>', {
        class: 'notifications_item_content',
    });
    let notificationTitle = $('<div>', {
        class: 'notification_title',
        html: notification.title,
    });
    let notificationText = $('<div>', {
        class: 'notification_text',
        html: notification.content,
    });

    notificationContent.append(notificationTitle, notificationText);
    notificationFlex.append(notificationContent);

    notificationItem.append(notificationFlex);

    if (notification.url) {
        let notificationLink = $('<a>', {
            class: 'notifications_item_link',
            href: notification.url,
        });
        let phrase = translate('def.goto');
        notificationLink.html(phrase);
        notificationLink.append('<i class="ph ph-arrow-right"></i>');

        notificationItem.addClass('with_link');

        notificationItem.append(notificationLink);
    }

    return notificationItem;
}

function readNavbarNotification(id) {
    readNotification(id.replace('notification_', ''));

    removeNotificationCount();
}

function closeNotifications() {
    $('.notifications_container').removeClass('opened');
}

function closeSearch() {
    $searchInput.parent().parent().removeClass('opened');
    $searchInput.parent().parent().removeClass('has-value');
    $searchInput.val('');
    $navbarContainer.show();
    $('.search-bg-panel').remove();
}

function openMiniProfile() {
    $('.miniprofile_container').addClass('opened');
    $('#openMiniProfile > i').addClass('opened');
}

function closeMiniProfile() {
    $('.miniprofile_container').removeClass('opened');
    $('#openMiniProfile > i').removeClass('opened');
}

function toggleMiniprofile() {
    if ($('.miniprofile_container').hasClass('opened')) {
        closeMiniProfile();
    } else {
        openMiniProfile();
        closeNotifications();
        closeSearch();
    }
}

function closeAll() {
    closeSearch();
    closeNotifications();
    closeMiniProfile();
    $('.submenu_container').empty();
    $('.standard_icons > li[data-child] > a').removeClass('active');

    const langContainer = $('.miniprofile_langs_container');
    const baseContainer = $('.miniprofile_base');

    if (langContainer.is(':visible')) {
        langContainer.hide();
        baseContainer.show();
    }
}

function toggleMiniprofileExpanded() {
    if ($('.miniprofile_container').hasClass('expanded')) {
        $('.miniprofile_container').removeClass('expanded');
        $('.miniprofile_container').find('.miniprofile_body').slideUp(250);
    } else {
        $('.miniprofile_container').addClass('expanded');
        $('.miniprofile_container').find('.miniprofile_body').slideDown(250);
    }
}

function toggleLangContainer() {
    toggleMiniprofile();
}

function toggleLang() {
    const langContainer = $('.miniprofile_langs_container');
    const baseContainer = $('.miniprofile_base');

    if (langContainer.is(':visible')) {
        langContainer.hide();
        baseContainer.show();
    } else {
        langContainer.show();
        baseContainer.hide();
    }
}

function checkActiveLang(e) {
    e.preventDefault();

    const href = $(this).attr('href');

    if (typeof driverObj !== 'undefined') driverObj.destroy();
    if( typeof closeLangNotify !== 'undefined' ) setLang($(this).data('lang'));

    if ($(this).hasClass('active'))
        return $(this).data('skip') ? closeMiniProfile() : toggleLang();

    localStorage.clear();

    window.location.href = href;
}

function toggleBurger() {
    let $menu = $('.navbar_mobile');

    $menu.toggleClass('opened');
}
