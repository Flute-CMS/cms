var driver = window.driver?.js?.driver;
var widgetConfigs = [];

var csrfToken = $('meta[name="csrf-token"]').attr('content');

// document.addEventListener('resume', () => {
//     window.location.reload();
// }, { capture: true });

// document.addEventListener('visibilitychange', () => {
//     if (!document.hidden) {
//         window.location.reload();
//     }
// });

htmx.onLoad(() => {
    $.ajaxSetup({
        dataType: 'json',
    });

    $.ajaxPrefilter(function (options, originalOptions, jqXHR) {
        if (options.type !== 'GET') {
            jqXHR.setRequestHeader('X-CSRF-Token', csrfToken);
        }
    });
});

document.addEventListener('click', function (event) {
    const el = event.target.closest('[data-copy]');
    if (!el) return;

    const data = el.getAttribute('data-copy');
    navigator.clipboard.writeText(data).catch(err => {
        console.error('Не удалось скопировать текст: ', err);
    });

    const parent = el.parentElement;
    let tEl = null;
    if (el.hasAttribute('data-tooltip')) {
        tEl = el;
    } else if (parent && parent.hasAttribute('data-tooltip')) {
        tEl = parent;
    }

    if (tEl) {
        const previous = tEl.getAttribute('data-tooltip');
        const copiedPhrase = translate('def.copied');

        tEl.setAttribute('data-tooltip', copiedPhrase);
        setTimeout(() => {
            tEl.setAttribute('data-tooltip', previous);
        }, 500);
    }
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

function getCookie(cname) {
    let name = cname + '=';
    let decodedCookie = decodeURIComponent(document.cookie);
    let ca = decodedCookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) === 0) {
            return c.substring(name.length, c.length);
        }
    }
    return '';
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
        data: { tip },
    });
}

async function getNotifications() {
    try {
        const response = await fetch(u(`api/notifications/unread`));
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        return data.result;
    } catch (error) {
        console.log(error);
        return [];
    }
}

async function getAllNotifications() {
    try {
        const response = await fetch(u(`api/notifications/all`));
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        return data.result;
    } catch (error) {
        console.log(error);
        return [];
    }
}

function clearNotifications() {
    $.ajax({
        url: u(`api/notifications`),
        type: 'DELETE',
    });
}

function readNotification(id) {
    $.ajax({
        url: u(`api/notifications/${id}`),
        type: 'PUT',
    });
}

function deleteNotification(id) {
    $.ajax({
        url: u(`api/notifications/${id}`),
        type: 'DELETE',
    });
}

// Очередь запросов
var queue = [];
var isProcessing = false;

async function batchTranslate(elements) {
    const translationsNeeded = [];
    const cacheResults = new Map();

    elements.forEach((el) => {
        const key = el.getAttribute('data-translate');
        const attribute = el.getAttribute('data-translate-attribute');
        const locale = $('html').attr('lang') || 'default';
        const replace = {};
        const serializedReplace = serializeReplace(replace);
        const cacheKey = `${locale}_${key}_${serializedReplace}`;

        let cachedTranslation = localStorage.getItem(cacheKey);
        if (cachedTranslation) {
            cacheResults.set(el, cachedTranslation);
        } else {
            translationsNeeded.push({
                el,
                key,
                attribute,
                locale,
                replace,
                cacheKey,
            });
        }
    });

    if (translationsNeeded.length > 0) {
        const isAdminPath = window.location.pathname.includes('admin');
        const response = await fetch(u(`${isAdminPath ? 'admin/api/translate' : 'api/translate'}`), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken,
            },
            body: JSON.stringify({
                translations: translationsNeeded.map(
                    ({ key, replace, locale }) => ({
                        phrase: key,
                        replace: replace,
                        locale: locale,
                    }),
                ),
            }),
        }).then((res) => res.json());

        response.forEach((result, index) => {
            const { el, cacheKey, attribute } = translationsNeeded[index];
            if (result.result !== undefined) {
                localStorage.setItem(cacheKey, result.result);
                cacheResults.set(el, result.result);
            }
        });
    }

    cacheResults.forEach((translation, element) => {
        if (element.getAttribute('data-translate-attribute')) {
            element.setAttribute(
                element.getAttribute('data-translate-attribute'),
                translation,
            );
        } else {
            element.textContent = translation;
        }
    });
}

function collectElementsForTranslation(addedNodes) {
    const elements = [];
    addedNodes.forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) {
            node.querySelectorAll(
                '[data-translate]:not([data-translate-loaded])',
            ).forEach((el) => {
                el.setAttribute('data-translate-loaded', 'true');
                elements.push(el);
            });
        }
    });
    return elements;
}

var updateTranslationsDebounced = (addedNodes) => {
    const elements = collectElementsForTranslation(addedNodes);
    if (elements.length) {
        batchTranslate(elements);
    }
};

var observerTranslations = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        if (mutation.type === 'childList' && mutation.addedNodes.length) {
            updateTranslationsDebounced(mutation.addedNodes);
        }
    });
});

var observerOptions = {
    childList: true,
    subtree: true,
};

observerTranslations.observe(document.body, observerOptions);

function serializeReplace(replace) {
    return JSON.stringify(Object.entries(replace).sort());
}

async function asyncTranslate(key, replace = {}, locale = null) {
    const serializedReplace = serializeReplace(replace);
    const cacheKey = `${locale}_${key}_${serializedReplace}`;
    let localStorageItem = localStorage.getItem(cacheKey);

    if (localStorageItem) {
        return localStorageItem;
    }

    try {
        const isAdminPath = window.location.pathname.includes('admin');
        const response = await $.ajax({
            url: u(`${isAdminPath ? 'admin/api/translate' : 'api/translate'}`),
            type: 'POST',
            data: JSON.stringify({
                translations: [
                    {
                        phrase: key,
                        replace: replace,
                        locale: locale,
                    },
                ],
            }),
            contentType: 'application/json',
            dataType: 'json',
        });

        const result = response[0].result;

        localStorage.setItem(`${locale}_${key}_${serializedReplace}`, result);

        return result;
    } catch (error) {
        console.log('TRANSLATE ERROR', error);
        return key;
    }
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

    const existingRequest = queue.find(
        (req) =>
            req.phrase === key &&
            req.locale === locale &&
            serializeReplace(req.replace) === serializedReplace,
    );

    if (existingRequest) {
        return `<span data-replaceplaceholder="${existingRequest.placeholder}" aria-busy="true"></span>`;
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

    return `<span data-replaceplaceholder="${placeholderId}" aria-busy="true"></span>`;
}

async function processQueue() {
    isProcessing = true;

    while (queue.length > 0) {
        let batch = queue.splice(0, queue.length);

        try {
            const isAdminPath = window.location.pathname.includes('admin');
            const response = await $.ajax({
                url: u(`${isAdminPath ? 'admin/api/translate' : 'api/translate'}`),
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
    if (!placeholderElements || placeholderElements.length === 0) return;

    placeholderElements.forEach((element) => {
        try {
            const attrTarget = element.getAttribute('data-translate-attribute');
            if (attrTarget) {
                const targetEl = element.parentElement || element;
                if (targetEl && typeof targetEl.setAttribute === 'function') {
                    targetEl.setAttribute(attrTarget, text);
                }
                element.remove();
                return;
            }

            const textNode = document.createTextNode(text);
            element.parentNode.replaceChild(textNode, element);
        } catch (e) {
            try {
                element.remove();
            } catch (_) {}
        }
    });
}

function copyToClipboard(text) {
    var textArea = document.createElement('textarea');
    textArea.style.position = 'fixed';
    textArea.style.top = 0;
    textArea.style.left = 0;

    textArea.style.width = '2em';
    textArea.style.height = '2em';

    textArea.style.padding = 0;

    textArea.style.border = 'none';
    textArea.style.outline = 'none';
    textArea.style.boxShadow = 'none';

    textArea.style.background = 'transparent';

    textArea.value = text;

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        var successful = document.execCommand('copy');
        var msg = successful ? 'successful' : 'unsuccessful';
        console.log('Copying text command was ' + msg);
    } catch (err) {
        console.log('Oops, unable to copy');
    }

    document.body.removeChild(textArea);
}

function u(url) {
    const baseSiteUrl = $('[name="site_url"]').attr('content') || '/';

    if (url === undefined || url === null || url === '' || url === false) {
        return baseSiteUrl;
    }

    if (/^(?:\w+:)?\/\/([^\s.]+\.\S{2}|localhost[\:?\d]*)\S*$/.test(url)) {
        return url;
    }

    url = url.toString().replace(/^\//, '');

    return `${baseSiteUrl}/${url}`;
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

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/sw.js')
            .then((registration) => {
                console.log(
                    'ServiceWorker registration successful with scope: ',
                    registration.scope,
                );
            })
            .catch((err) => {
                console.log('ServiceWorker registration failed: ', err);
            });
    });
}

window.addEventListener('online', function () {
    document.body.classList.remove('offline');
    if (window.location.pathname === '/offline') {
        window.location.href = '/';
    }
});

window.addEventListener('offline', function () {
    document.body.classList.add('offline');
});
