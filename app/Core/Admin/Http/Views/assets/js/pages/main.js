$('[data-id]').on('click', function (e) {
    e.preventDefault();
    let el = $(this);
    let tabId = el.data('id');

    if (el.hasClass('active')) return;

    // Обновляем классы для вкладок и контента
    $('[data-id]').not(el).removeClass('active');
    el.addClass('active');
    $('.settings-container > div').removeClass('active');
    $(`.settings-container > #${tabId}`).addClass('active');

    // Обновляем URL без перезагрузки страницы
    if (history.pushState) {
        let newUrl =
            window.location.protocol +
            '//' +
            window.location.host +
            window.location.pathname +
            '?tab=' +
            tabId;
        window.history.pushState({ path: newUrl }, '', newUrl);
    }
});

// Функция для обработки параметра tab при загрузке страницы
function handleTabParameter() {
    let params = new URLSearchParams(window.location.search);
    let tab = params.get('tab') ?? 'app';

    if (tab) {
        let el = $(`[data-id=${tab}]`);
        if (el.length) {
            el.click(); // Активируем вкладку если она существует
        }
    }
}

let db_connections = $('[data-database]')
    .map(function () {
        return {
            value: $(this).data('database'),
            text: $(this).text(),
        };
    })
    .get();

// Вызываем функцию при загрузке страницы
$(document).ready(function () {
    handleTabParameter();

    document.getElementById('getMyIp').addEventListener('click', function () {
        fetch(u('admin/api/getip'), {
            headers: {
                "x-csrf-token":csrfToken,
            },
        })
            .then((response) => response.text())
            .then((ip) => {
                document.getElementById('getMyIp').hidden = true;
                document.getElementById('myIp').textContent = JSON.parse(ip).ip;
            })
            .catch((error) => {
                document.getElementById('myIp').textContent =
                    'Ошибка при получении IP-адреса';
            });
    });

    $('[data-deletedb]').on('click', async function () {
        let dbName = $(this).data('deletedb');

        let phrase = translate('admin.confirm_delete_db', {
            name: dbName,
        });

        if (confirm(phrase)) {
            if (
                sendRequest(
                    { dbName: dbName, func: 'deletedb' },
                    `admin/api/settings/` + $('[data-id].active').data('id'),
                )?.success
            )
                $(this).parent().parent().remove();
        }
    });

    $('[data-deleteconnection]').on('click', async function () {
        let connectionName = $(this).data('deleteconnection');

        let phrase = translate('admin.confirm_delete_connection', {
            name: connectionName,
        });

        if (confirm(phrase)) {
            if (
                sendRequest(
                    {
                        dbConnectionName: connectionName,
                        func: 'deleteconnection',
                    },
                    `admin/api/settings/` + $('[data-id].active').data('id'),
                )?.success
            )
                $(this).parent().parent().remove();
        }
    });

    $(document).on(
        'submit',
        '.settings-container > div.active > form',
        (ev) => {
            let $form = $(ev.currentTarget);

            ev.preventDefault();

            if (ev.target.checkValidity()) {
                sendRequest(
                    serializeForm($form),
                    `admin/api/settings/` + $('[data-id].active').data('id'),
                );
            }
        },
    );

    $(document).on('submit', '#dbForm', (ev) => {
        let $form = $(ev.currentTarget);

        ev.preventDefault();

        if (ev.target.checkValidity()) {
            sendRequest(
                {
                    ...serializeForm($form),
                    ...{
                        func: 'createdb',
                    },
                },
                `admin/api/settings/database`,
            );
        }
    });

    $(document).on('submit', '#dbConnectionForm', (ev) => {
        let $form = $(ev.currentTarget);

        ev.preventDefault();

        if (ev.target.checkValidity()) {
            sendRequest(
                {
                    ...serializeForm($form),
                    ...{
                        func: 'addconnection',
                    },
                },
                `admin/api/settings/database`,
            );
        }
    });

    $(document).on('submit', '#dbEditForm', (ev) => {
        let $form = $(ev.currentTarget);

        ev.preventDefault();

        if (ev.target.checkValidity()) {
            sendRequest(
                {
                    ...serializeForm($form),
                    ...{
                        func: 'editdb',
                    },
                },
                `admin/api/settings/database`,
            );
        }
    });

    $(document).on('submit', '#dbEditConnectionForm', (ev) => {
        let $form = $(ev.currentTarget);

        ev.preventDefault();

        if (ev.target.checkValidity()) {
            sendRequest(
                {
                    ...serializeForm($form),
                    ...{
                        func: 'editconnection',
                    },
                },
                `admin/api/settings/database`,
            );
        }
    });

    $('[data-addb]').on('click', async () => {
        let modalId = await Modals.open({
            title: translate('admin.add_db_title'),
            closeOnBackground: false,
            content: {
                form: await createDb(),
            },
            buttons: [
                {
                    text: translate('def.save'),
                    class: 'primary',
                    id: 'saveDb',
                    callback: (modalInstance) => {
                        $('#saveDb').attr('form', 'dbForm');
                    },
                },
                {
                    text: translate('def.cancel'),
                    class: 'error',
                    callback: (modalInstance) => {
                        modalInstance.close(modalId.id);
                    },
                },
            ],
            type: 'modal',
        });
    });

    $('[data-changedb]').on('click', async function (e) {
        let dbName = $(e.currentTarget).data('changedb');
        let connectionData = $(e.currentTarget).data('values');

        let modalId = await Modals.open({
            title: translate('admin.edit_db_title', { name: dbName }),
            closeOnBackground: false,
            content: {
                form: await createDb(connectionData, dbName),
            },
            buttons: [
                {
                    text: translate('def.save'),
                    class: 'primary',
                    id: 'saveDb',
                    callback: (modalInstance) => {
                        $('#saveDb').attr('form', 'dbEditForm');
                    },
                },
                {
                    text: translate('def.cancel'),
                    class: 'error',
                    callback: (modalInstance) => {
                        modalInstance.close(modalId.id);
                    },
                },
            ],
            type: 'modal',
        });
    });

    $('[data-addconnection]').on('click', async () => {
        let modalId = await Modals.open({
            title: translate('admin.add_connection_title'),
            closeOnBackground: false,
            content: {
                form: await createDbConnectionFormConfig(),
            },
            buttons: [
                {
                    text: translate('def.save'),
                    class: 'primary',
                    id: 'saveDb',
                    callback: (modalInstance) => {
                        $('#saveDb').attr('form', 'dbConnectionForm');
                    },
                },
                {
                    text: translate('def.cancel'),
                    class: 'error',
                    callback: (modalInstance) => {
                        modalInstance.close(modalId.id);
                    },
                },
            ],
            type: 'modal',
        });
    });

    $('[data-changeconnection]').on('click', async function (e) {
        let connectionData = $(this).data('values');
        let connName = $(this).data('changeconnection');

        let modalId = await Modals.open({
            title: translate(`admin.edit_connection_title`, { name: connName }),
            closeOnBackground: false,
            content: {
                form: await createDbConnectionFormConfig(
                    connectionData,
                    connName,
                ),
            },
            buttons: [
                {
                    text: translate('def.save'),
                    class: 'primary',
                    id: 'saveDb',
                    callback: (modalInstance) => {
                        $('#saveDb').attr('form', 'dbEditConnectionForm');

                        // Логика сохранения измененных данных
                        // modalInstance.close(modalId);
                    },
                },
                {
                    text: translate('def.cancel'),
                    class: 'error',
                    callback: (modalInstance) => {
                        modalInstance.close(modalId.id);
                    },
                },
            ],
            type: 'modal',
        });
    });
});

async function createDb(defaultValues = {}, name = null) {
    return {
        id: Object.keys(defaultValues).length > 0 ? 'dbEditForm' : 'dbForm',
        fields: [
            {
                type: 'hidden',
                id: 'lastDbName',
                default: name ?? '',
            },
            {
                type: 'select',
                id: 'defaultDatabase',
                label: translate('admin.default_db'),
                helpText: translate('admin.default_db_help'),
                required: true,
                options: db_connections,
                default: defaultValues.connection,
            },
            {
                type: 'text',
                id: 'tablePrefix',
                label: translate('admin.table_prefix'),
                placeholder: translate('admin.table_prefix_placeholder'),
                helpText: translate('admin.table_prefix_help'),
                default: defaultValues.prefix,
            },
            {
                type: 'text',
                id: 'dbName',
                label: translate('admin.db_name'),
                placeholder: translate('admin.db_name_placeholder'),
                required: true,
                default: name,
            },
        ],
    };
}

async function createDbConnectionFormConfig(defaultValues = {}, name = null) {
    // ЕБАШИМ КОСТЫЛИ. АХАХАХХАХАХА
    let connDetails = {};

    if (defaultValues.connection) {
        let test = defaultValues.connection.split(';');

        for (let val of test) {
            let valueSplitten = val.split('=');

            let splittenHost = valueSplitten[0].split(':');

            if (splittenHost[1] === 'host') {
                connDetails.driver = splittenHost[0];
                connDetails.host = valueSplitten[1];
            } else {
                connDetails[valueSplitten[0]] = valueSplitten[1];
            }
        }
    }

    return {
        id:
            Object.keys(defaultValues).length > 0
                ? 'dbEditConnectionForm'
                : 'dbConnectionForm',
        fields: [
            {
                type: 'hidden',
                id: 'lastDbConnectionName',
                default: name ?? '',
            },
            {
                type: 'text',
                id: 'dbConnectionName',
                label: translate('admin.db_connection_name'),
                placeholder: translate('admin.db_connection_name_placeholder'),
                required: true,
                default: name ?? '',
            },
            {
                type: 'select',
                id: 'dbDriver',
                label: translate('admin.db_driver'),
                required: true,
                options: [
                    { value: 'mysql', text: translate('admin.mysql') },
                    {
                        value: 'postgresql',
                        text: translate('admin.postgresql'),
                    },
                    { value: 'sqlite', text: translate('admin.sqlite') },
                    // Другие драйверы...
                ],
                default: connDetails.driver,
            },
            {
                type: 'text',
                id: 'dbHost',
                label: translate('admin.db_host'),
                placeholder: translate('admin.db_host_placeholder'),
                required: true,
                default: connDetails.host,
            },
            {
                type: 'text',
                id: 'dbName',
                label: translate('admin.db_name'),
                placeholder: translate('admin.db_name_placeholder'),
                required: true,
                default: connDetails.dbname,
            },
            {
                type: 'text',
                id: 'dbUser',
                label: translate('admin.db_user'),
                placeholder: translate('admin.db_user_placeholder'),
                required: true,
                default: defaultValues.username,
            },
            {
                type: 'password',
                id: 'dbPassword',
                label: translate('admin.db_password'),
                placeholder: translate('admin.db_password_placeholder'),
                default: defaultValues.password,
            },
            {
                type: 'number',
                id: 'dbPort',
                label: translate('admin.db_port'),
                placeholder: translate('admin.db_port_placeholder'),
                default: connDetails.port || 3306,
                required: true,
            },
        ],
    };
}
document
    .getElementById('rememberMeDuration')
    .addEventListener('input', function (e) {
        let seconds = parseInt(e.target.value, 10);
        let lang = document.documentElement.lang;
        let readableDuration = secondsToReadable(seconds, lang);
        document.getElementById('durationReadable').textContent =
            readableDuration;
    });

function secondsToReadable(seconds, lang) {
    if (isNaN(seconds) || seconds === 0) {
        return '';
    }

    let months = Math.floor(seconds / (3600 * 24 * 30.44)); // Average number of seconds in a month
    seconds -= months * (3600 * 24 * 30.44);

    let weeks = Math.floor(seconds / (3600 * 24 * 7));
    seconds -= weeks * (3600 * 24 * 7);

    let days = Math.floor(seconds / (3600 * 24));
    seconds -= days * (3600 * 24);

    let hours = Math.floor(seconds / 3600);
    seconds -= hours * 3600;

    let minutes = Math.floor(seconds / 60);
    seconds -= minutes * 60;

    if (lang === 'ru') {
        return formatDurationRu(months, weeks, days, hours, minutes, seconds);
    } else {
        return formatDurationEn(months, weeks, days, hours, minutes, seconds);
    }
}

function formatDurationRu(months, weeks, days, hours, minutes, seconds) {
    let parts = [];
    if (months > 0) {
        parts.push(
            months + ' ' + pluralRu(months, ['месяц', 'месяца', 'месяцев']),
        );
    }
    if (weeks > 0) {
        parts.push(
            weeks + ' ' + pluralRu(weeks, ['неделя', 'недели', 'недель']),
        );
    }
    if (days > 0) {
        parts.push(days + ' ' + pluralRu(days, ['день', 'дня', 'дней']));
    }
    if (hours > 0) {
        parts.push(hours + ' ' + pluralRu(hours, ['час', 'часа', 'часов']));
    }
    if (minutes > 0) {
        parts.push(
            minutes + ' ' + pluralRu(minutes, ['минута', 'минуты', 'минут']),
        );
    }
    if (seconds > 0) {
        parts.push(
            seconds + ' ' + pluralRu(seconds, ['секунда', 'секунды', 'секунд']),
        );
    }
    return parts.join(' и ');
}

function formatDurationEn(months, weeks, days, hours, minutes, seconds) {
    let parts = [];
    if (months > 0) {
        parts.push(months + ' ' + (months === 1 ? 'month' : 'months'));
    }
    if (weeks > 0) {
        parts.push(weeks + ' ' + (weeks === 1 ? 'week' : 'weeks'));
    }
    if (days > 0) {
        parts.push(days + ' ' + (days === 1 ? 'day' : 'days'));
    }
    if (hours > 0) {
        parts.push(hours + ' ' + (hours === 1 ? 'hour' : 'hours'));
    }
    if (minutes > 0) {
        parts.push(minutes + ' ' + (minutes === 1 ? 'minute' : 'minutes'));
    }
    if (seconds > 0) {
        parts.push(seconds + ' ' + (seconds === 1 ? 'second' : 'seconds'));
    }
    return parts.join(' and ');
}

function pluralRu(n, forms) {
    return forms[
        n % 10 === 1 && n % 100 !== 11
            ? 0
            : n % 10 >= 2 && n % 10 <= 4 && (n % 100 < 10 || n % 100 >= 20)
            ? 1
            : 2
    ];
}

// Инициализация при загрузке страницы
window.onload = function () {
    let initialSeconds = document.getElementById('rememberMeDuration').value;
    let lang = document.documentElement.lang;
    let readableDuration = secondsToReadable(
        parseInt(initialSeconds, 10),
        lang,
    );
    document.getElementById('durationReadable').textContent = readableDuration;
};
