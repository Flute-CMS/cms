const driver = window.driver?.js?.driver;

let csrfToken = $('meta[name="csrf-token"]').attr('content');
$.ajaxPrefilter(function (options, originalOptions, jqXHR) {
    if (options.type !== 'GET')
        jqXHR.setRequestHeader('x-csrf-token', csrfToken);
});

$(document).on('click', '[data-copy]', (e) => {
    let data = $(e.currentTarget).data('copy');
    navigator.clipboard.writeText(data);
});

function setCookie(name, value, days) {
    var expires = '';
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
        expires = '; expires=' + date.toUTCString();
    }
    document.cookie = name + '=' + (value || '') + expires + '; path=/';
}

function getCookie(name) {
    var nameEQ = name + '=';
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

function eraseCookie(name) {
    document.cookie =
        name + '=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}

function debounce(func, delay) {
    let debounceTimer;
    return function () {
        const context = this;
        const args = arguments;
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => func.apply(context, args), delay);
    };
}

function completeTip(tip) {
    $.ajax({
        url: u(`api/tip/complete`),
        type: 'POST',
        async: false,
        data: { tip },
    });
}

function getNotifications() {
    let notifications = [];

    $.ajax({
        url: u(`api/notifications/unread`),
        type: 'GET',
        async: false,
        success: function (response) {
            notifications = response.result;
        },
        error: function (error) {
            console.log(error);
        },
    });
    return notifications;
}

function getAllNotifications() {
    let notifications = [];

    $.ajax({
        url: u(`api/notifications/all`),
        type: 'GET',
        async: false,
        success: function (response) {
            notifications = response.result;
        },
    });
    return notifications;
}

function clearNotifications() {
    $.ajax({
        url: u(`api/notifications`),
        type: 'DELETE',
        async: false,
    });
}

function readNotification(id) {
    $.ajax({
        url: u(`api/notifications/${id}`),
        type: 'PUT',
        async: false,
    });
}

function deleteNotification(id) {
    $.ajax({
        url: u(`api/notifications/${id}`),
        type: 'DELETE',
        async: false,
    });
}

// Очередь запросов
let queue = [];
let isProcessing = false;

function serializeReplace(replace) {
    return JSON.stringify(Object.entries(replace).sort());
}

function t(key, replace = {}, locale = null) {
    return translate(key, replace, locale);
}

function translate(key, replace = {}, locale = null) {
    if (locale === null) {
        locale = $('html').attr('lang');
    }

    const serializedReplace = serializeReplace(replace);
    const cacheKey = `${locale}_${key}_${serializedReplace}`;
    let localStorageItem = localStorage.getItem(cacheKey);

    if (localStorageItem) {
        return localStorageItem;
    }

    // Проверяем, находится ли перевод в очереди
    const existingRequest = queue.find(
        (req) =>
            req.phrase === key &&
            req.locale === locale &&
            serializeReplace(req.replace) === serializedReplace,
    );

    if (existingRequest) {
        // Возвращаем плейсхолдер, если запрос уже в очереди
        return `<span data-replaceplaceholder="${existingRequest.placeholder}">🕐</span>`;
    }

    let placeholderId = `translation-placeholder-${uuidv4()}`;
    queue.push({
        phrase: key,
        replace: replace,
        locale: locale,
        placeholder: placeholderId,
    });

    if (!isProcessing) {
        setTimeout(processQueue, 100);
        isProcessing = true;
    }

    return `<span data-replaceplaceholder="${placeholderId}">🕐</span>`;
}

async function processQueue() {
    isProcessing = true;

    while (queue.length > 0) {
        let batch = queue.splice(0, queue.length);

        try {
            const response = await $.ajax({
                url: u(`api/translate`),
                type: 'POST',
                data: JSON.stringify({
                    translations: batch.map((item) => ({
                        phrase: item.phrase,
                        replace: item.replace,
                        locale: item.locale,
                    })),
                }),
                contentType: 'application/json',
                dataType: 'json',
            });

            response.forEach((item) => {
                let correspondingRequest = batch.find(
                    (req) => req.phrase === item.key,
                );
                if (correspondingRequest) {
                    let result = item.result;
                    const serializedReplace = serializeReplace(
                        correspondingRequest.replace,
                    );
                    localStorage.setItem(
                        `${correspondingRequest.locale}_${correspondingRequest.phrase}_${serializedReplace}`,
                        result,
                    );
                    replacePlaceholder(
                        correspondingRequest.placeholder,
                        result,
                    );
                }
            });
        } catch (error) {
            console.error('TRANSLATE ERROR', error);
            batch.forEach((item) => {
                replacePlaceholder(item.placeholder, 'Translation Error');
            });
        }
    }

    isProcessing = false;
}

function replacePlaceholder(placeholderId, text) {
    let placeholderElements = document.querySelectorAll(
        `[data-replaceplaceholder="${placeholderId}"]`,
    );
    if (placeholderElements) {
        placeholderElements.forEach((element) => {
            let textNode = document.createTextNode(text);
            element.parentNode.replaceChild(textNode, element);
        });
    }
}

function u(url) {
    if (/^(?:\w+:)?\/\/([^\s.]+\.\S{2}|localhost[\:?\d]*)\S*$/.test(url)) {
        return url;
    } else {
        return `${SITE_URL}/${url}`;
    }
}

function make(tag, classNames = null, attributes = {}) {
    let el = document.createElement(tag);

    if (Array.isArray(classNames)) {
        el.classList.add(...classNames);
    } else if (typeof classNames === 'string') {
        el.classList.add(classNames);
    }

    for (let attrName in attributes) {
        el[attrName] = attributes[attrName];
    }

    return el;
}

function uuidv4() {
    return ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, (c) =>
        (
            c ^
            (crypto.getRandomValues(new Uint8Array(1))[0] & (15 >> (c / 4)))
        ).toString(16),
    );
}

function loadWidget(params, id, interval = 0) {
    console.log('%cWIDGET LOADING - ' + id, 'color: white; font-size: 16px');

    $.ajax({
        url: u(`widget/show`),
        type: 'POST',
        data: { params },
        success: function (response) {
            let el = $(`#${id}`);

            if (response?.assets && !el.hasClass('loaded')) {
                for (let i = 0; i < response.assets.length; i++) {
                    $('head').append(response.assets[i]);
                }
            }

            // У меня не хватило фантазии на реализацию ожидания, поэтому мы просто ждем пару МС чтобы все подгрузилось
            if (response?.assets)
                setTimeout(() => el.html(response?.html), 500);
            else el.html(response?.html);

            if (!el.hasClass('loaded')) el.addClass('loaded');

            if (interval > 0)
                setInterval(() => {
                    loadWidget(params, id);
                }, interval);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            $(`#${id}`).remove();

            let jsonError = jqXHR.responseJSON?.error?.message;

            console.error(
                '%cWIDGET LOADER ERROR - ' + jsonError,
                'color: white; font-size: 16px;',
            );
        },
    });
}

/**
 * Add or update a query string parameter. If no URI is given, we use the current
 * window.location.href value for the URI.
 *
 * Based on the DOM URL parser described here:
 * http://james.padolsey.com/javascript/parsing-urls-with-the-dom/
 *
 * @param   (string)    uri     Optional: The URI to add or update a parameter in
 * @param   (string)    key     The key to add or update
 * @param   (string)    value   The new value to set for key
 *
 * Tested on Chrome 34, Firefox 29, IE 7 and 11
 */
function appendGet(uri, key, value) {
    // Use window URL if no query string is provided
    if (!uri) {
        uri = window.location.href;
    }

    // Create a dummy element to parse the URI with
    var a = document.createElement('a'),
        // match the key, optional square brackets, an equals sign or end of string, the optional value
        reg_ex = new RegExp(key + '((?:\\[[^\\]]*\\])?)(=|$)(.*)'),
        // Setup some additional variables
        qs,
        qs_len,
        key_found = false;

    // Use the JS API to parse the URI
    a.href = uri;

    // If the URI doesn't have a query string, add it and return
    if (!a.search) {
        a.search = '?' + key + '=' + value;

        return a.href;
    }

    // Split the query string by ampersands
    qs = a.search.replace(/^\?/, '').split(/&(?:amp;)?/);
    qs_len = qs.length;

    // Loop through each query string part
    while (qs_len > 0) {
        qs_len--;

        // Remove empty elements to prevent double ampersands
        if (!qs[qs_len]) {
            qs.splice(qs_len, 1);
            continue;
        }

        // Check if the current part matches our key
        if (reg_ex.test(qs[qs_len])) {
            // Replace the current value
            qs[qs_len] = qs[qs_len].replace(reg_ex, key + '$1') + '=' + value;

            key_found = true;
        }
    }

    // If we haven't replaced any occurrences above, add the new parameter and value
    if (!key_found) {
        qs.push(key + '=' + value);
    }

    // Set the new query string
    a.search = '?' + qs.join('&');

    return a.href;
}
