function serializeForm($form) {
    if (
        $form.closest('.tab-content:not([hidden])').length === 0 &&
        $form.closest('.modal').length === 0
    ) {
        console.log('Форма находится в скрытой вкладке и не будет обработана.');
        return {};
    }

    let formData = $form.serializeArray();
    let paramObj = {};
    let additionalParams = {};

    // Process standard fields
    formData.forEach(function (kv) {
        if (kv.name === 'paramNames[]' || kv.name === 'paramValues[]') {
            // Skip processing here, handle in additional parameters
        } else {
            paramObj[kv.name] = kv.value;
        }
    });

    // Process dynamic additional parameters
    let paramNames = $form
        .find('input[name="paramNames[]"]')
        .map(function () {
            return $(this).val();
        })
        .get();
    let paramValues = $form
        .find('input[name="paramValues[]"]')
        .map(function () {
            return $(this).val();
        })
        .get();

    // Добавляем неотмеченные чекбоксы
    $form.find('input[type="checkbox"]').each(function () {
        paramObj[this.name] = this.checked;
    });

    paramNames.forEach(function (name, index) {
        if (name) {
            // Only add parameter if name is not empty
            additionalParams[name] = paramValues[index] || '';
        }
    });

    if (
        $form.find('.editor-ace').length > 0 &&
        !$form.find('.editor-ace').closest('.tab-content[hidden]').length
    ) {
        paramObj.editorContent = ace
            .edit($form.find('.editor-ace')[0])
            .getValue();
    }

    // Assign additional parameters to a specific key, or directly to paramObj
    paramObj.additional = additionalParams;

    return paramObj;
}

function replaceURLForTab(url) {
    return url
        .replace(/\/(edit|delete|add)\/\d+$/, '/list')
        .replace('/add', '/list');
}

function transformUrl(url) {
    const regex = /admin\/api\/([^\/]+\/?[^\/]*)\/(\d+)/;

    const match = url.match(regex);
    if (match) {
        const page = match[1];
        const id = match[2];

        return `/admin/${page}/edit/${id}`;
    } else {
        return url;
    }
}

function sendRequest(data, path = null, method = 'POST', callback) {
    toast({
        type: 'async',
        message: translate('admin.is_loading'),
        fetchFunction: () =>
            new Promise((resolve, reject) => {
                $.ajax({
                    url: u(path),
                    type: method,
                    data: data,
                    success: function (response) {
                        callback && callback(response);
                        Modals.clear();

                        if (method === 'DELETE') {
                            tryAndDeleteTab(transformUrl(path));
                            refreshCurrentPage();
                        } else {
                            refreshCurrentPage();
                            if (!path.includes('admin/api/settings')) {
                                if (
                                    path.includes('edit') ||
                                    path.includes('add') ||
                                    path.includes('delete')
                                )
                                    fetchContentAndAddTab(
                                        replaceURLForTab(
                                            window.location.pathname,
                                        ),
                                    );
                                refreshCurrentPage();
                            }
                        }
                        resolve(response?.success || translate('def.success'));
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        console.error(
                            'error request',
                            jqXHR,
                            textStatus,
                            errorThrown,
                        );
                        callback && callback(jqXHR);
                        reject(
                            jqXHR.responseJSON?.error ??
                                translate('def.unknown_error'),
                        );
                    },
                });
            }),
    });
}

function serializeFormData($form) {
    // Ensure form is in an active (visible) tab content
    if ($form.closest('.tab-content:not([hidden])').length === 0) {
        console.log('Form is in a hidden tab and will not be processed.');
        return null; // Form is in a hidden tab, do not process data
    }

    let formData = new FormData($form[0]);
    let additionalParams = {};

    // Process dynamic additional parameters
    $form.find('input[name="paramNames[]"]').each(function (index) {
        let name = $(this).val();
        let value =
            $form.find('input[name="paramValues[]"]').eq(index).val() || '';
        if (name) {
            additionalParams[name] = value;
        }
    });

    // Add unchecked checkboxes
    $form.find('input[type="checkbox"]').each(function () {
        formData.set(this.name, this.checked);
    });

    // Include editor content if applicable
    if ($form.find('.editor-ace').length > 0) {
        formData.set(
            'editorContent',
            ace.edit($form.find('.editor-ace')[0]).getValue(),
        );
    }

    // Append additional parameters to formData
    Object.keys(additionalParams).forEach((key) => {
        formData.append(key, additionalParams[key]);
    });

    return formData;
}

function sendRequestFormData(
    data,
    path = null,
    method = 'POST',
    callback = null,
) {
    let result = null;

    toast({
        type: 'async',
        message: translate('admin.is_loading'),
        fetchFunction: () =>
            new Promise((resolve, reject) => {
                $.ajax({
                    url: u(path),
                    type: method,
                    data: data,
                    contentType: false,
                    processData: false,
                    success: function (response) {
                        callback && callback(response);

                        result = response;

                        Modals.clear();

                        if (method === 'DELETE') {
                            tryAndDeleteTab(transformUrl(path));

                            refreshCurrentPage();
                        } else {
                            refreshCurrentPage();

                            if (!path.includes('admin/api/settings')) {
                                // $('button[type="submit"]').attr('disabled', true);

                                if (
                                    path.includes('edit') ||
                                    path.includes('add') ||
                                    path.includes('delete')
                                )
                                    fetchContentAndAddTab(
                                        replaceURLForTab(
                                            window.location.pathname,
                                        ),
                                    );

                                refreshCurrentPage();
                            }
                        }
                        resolve(response?.success || translate('def.success'));
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        console.error(
                            'error request',
                            jqXHR,
                            textStatus,
                            errorThrown,
                        );
                        result = jqXHR.responseJSON;

                        callback && callback(jqXHR);
                        reject(
                            jqXHR.responseJSON?.error ??
                                translate('def.unknown_error'),
                        );
                    },
                });
            }),
    });

    return result;
}

function addToggleButton($input) {
    if ($input.next('.toggle-visibility').length === 0) {
        const $container = $('<div class="password-input-container"></div>');
        const $toggleButton = $(
            '<button type="button" class="toggle-visibility"><i class="ph ph-eye"></i></button>',
        );

        $toggleButton.on('click', function () {
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $toggleButton.html('<i class="ph ph-eye-closed"></i>');
            } else {
                $input.attr('type', 'password');
                $toggleButton.html('<i class="ph ph-eye"></i>');
            }
        });

        $input.wrap($container);
        $input.after($toggleButton);
    }
}

$(function () {
    $('.editor-ace').each(function () {
        let editor = ace.edit(this);
        editor.setTheme('ace/theme/solarized_dark');
        editor.session.setMode('ace/mode/json');
    });

    $('input[type="password"]').each(function () {
        addToggleButton($(this));
    });

    // Alternative setup using MutationObserver
    const inputPasswordObserver = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.addedNodes.length) {
                $(mutation.addedNodes)
                    .find('input[type="password"]')
                    .each(function () {
                        addToggleButton($(this));
                    });
            }
        });
    });

    inputPasswordObserver.observe(document.body, {
        childList: true,
        subtree: true,
    });

    $(document).on('submit', '[data-form]', async (ev) => {
        let $form = $(ev.currentTarget);

        ev.preventDefault();

        if ($form.closest('.tab-content:not([hidden])').length === 0) {
            console.log('Ignoring submission from a hidden tab.');
            return;
        }

        let path = $form.data('form'),
            form = serializeForm($form),
            page = $form.data('page');

        if (!form) return;

        let url = `admin/api/${page}/${path}`,
            method = 'POST';

        if (path === 'edit') {
            url = `admin/api/${page}/${form.id}`;
            method = 'PUT';
        }

        let activeEditorElement = $form.find('[data-editorjs]');

        if (activeEditorElement) {
            let editorId = activeEditorElement.attr('id');
            let activeEditor = window['editorInstance_' + editorId];

            if (activeEditor) {
                let editorData = await activeEditor.save();
                form['blocks'] = JSON.stringify(editorData.blocks);

                localStorage.removeItem('editorData_' + editorId);
                window.defaultEditorData[editorId] = {};
            }
        }

        if (ev.target.checkValidity()) {
            sendRequest(form, url, method);
        }
    });

    $(document).on('click', '[data-deleteaction]', async function () {
        let id = $(this).data('deleteaction'),
            path = $(this).data('deletepath');

        if (await asyncConfirm(translate('admin.confirm_delete'))) {
            sendRequest({}, 'admin/api/' + path + '/' + id, 'DELETE');

            // $(this).parent().parent().parent().remove();
        }
    });

    $(document).on('input', '#icon', function () {
        let val = $(this).val().trim();
        $('#icon-output').html(val);
    });

    $(document).on('click', '[data-faq]', (e) => {
        e.preventDefault();

        let el = $(e.currentTarget);

        const title = el.data('faq');
        const answer = el.data('faq-content');

        const content = {
            faq: {
                answer: answer,
            },
        };

        Modals.open({
            title: title,
            content,
        });
    });

    const container = document.querySelector('.icon-container');
    const text = container.querySelector('.icon-text');

    container.addEventListener('mouseenter', function () {
        const tempSpan = document.createElement('span');
        tempSpan.style.visibility = 'hidden';
        tempSpan.style.whiteSpace = 'nowrap';
        tempSpan.textContent = text.textContent;
        document.body.appendChild(tempSpan);

        const textWidth = tempSpan.offsetWidth;
        container.style.width = `${30 + textWidth}px`;
        document.body.removeChild(tempSpan);
    });

    container.addEventListener('mouseleave', function () {
        container.style.width = '35px';
    });
});

window.defaultEditorData = {};
