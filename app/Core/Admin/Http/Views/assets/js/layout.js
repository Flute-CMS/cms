function serializeForm($form) {
    if (isFormInHiddenTab($form)) {
        console.log('Форма находится в скрытой вкладке и не будет обработана.');
        return {};
    }

    let formData = $form.serializeArray();
    let paramObj = serializeStandardFields(formData);
    let additionalParams = serializeDynamicParams($form);

    // Добавляем неотмеченные чекбоксы
    addUncheckedCheckboxes($form, paramObj);

    // Include editor content if applicable
    if (shouldIncludeEditorContent($form)) {
        paramObj.editorContent = ace
            .edit($form.find('.editor-ace')[0])
            .getValue();
    }

    // Assign additional parameters to a specific key, or directly to paramObj
    paramObj.additional = additionalParams;

    return paramObj;
}

function isFormInHiddenTab($form) {
    return (
        $form.closest('.tab-content:not([hidden])').length === 0 &&
        $form.closest('.modal').length === 0
    );
}

function serializeStandardFields(formData) {
    let paramObj = {};
    formData.forEach(function (kv) {
        if (kv.name !== 'paramNames[]' && kv.name !== 'paramValues[]') {
            paramObj[kv.name] = kv.value;
        }
    });
    return paramObj;
}

function serializeDynamicParams($form) {
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

    let additionalParams = {};
    paramNames.forEach(function (name, index) {
        if (name) {
            additionalParams[name] = paramValues[index] || '';
        }
    });
    return additionalParams;
}

function addUncheckedCheckboxes($form, paramObj) {
    $form.find('input[type="checkbox"]').each(function () {
        paramObj[this.name] = this.checked;
    });
}

function shouldIncludeEditorContent($form) {
    return (
        $form.find('.editor-ace').length > 0 &&
        !$form.find('.editor-ace').closest('.tab-content[hidden]').length
    );
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

function sendRequest(
    data,
    path = null,
    method = 'POST',
    callback = null,
    needToRefresh = true,
) {
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
                        handleRequestSuccess(
                            response,
                            path,
                            method,
                            needToRefresh,
                            callback,
                        );
                        resolve(response?.success || translate('def.success'));
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        handleRequestError(jqXHR, callback);
                        reject(
                            jqXHR.responseJSON?.error ??
                                translate('def.unknown_error'),
                        );
                    },
                });
            }),
    });
}

function handleRequestSuccess(response, path, method, needToRefresh, callback) {
    callback && callback(response);

    if (needToRefresh) {
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
                        replaceURLForTab(window.location.pathname),
                    );
                refreshCurrentPage();
            }
        }
    }
}

function handleRequestError(jqXHR, callback) {
    console.error('error request', jqXHR);
    callback && callback(jqXHR);
}

function serializeFormData($form) {
    if (isFormInHiddenTab($form)) {
        console.log('Form is in a hidden tab and will not be processed.');
        return null;
    }

    let formData = new FormData($form[0]);
    let additionalParams = serializeDynamicParams($form);

    addUncheckedCheckboxes($form, formData);
    includeEditorContentIfApplicable($form, formData);
    appendAdditionalParams(formData, additionalParams);

    return formData;
}

function includeEditorContentIfApplicable($form, formData) {
    if ($form.find('.editor-ace').length > 0) {
        formData.set(
            'editorContent',
            ace.edit($form.find('.editor-ace')[0]).getValue(),
        );
    }
}

function appendAdditionalParams(formData, additionalParams) {
    Object.keys(additionalParams).forEach((key) => {
        formData.append(key, additionalParams[key]);
    });
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
                        handleRequestFormDataSuccess(
                            response,
                            path,
                            method,
                            callback,
                        );
                        result = response;
                        resolve(response?.success || translate('def.success'));
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        handleRequestError(jqXHR, callback);
                        result = jqXHR.responseJSON;
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

function handleRequestFormDataSuccess(response, path, method, callback) {
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
                    replaceURLForTab(window.location.pathname),
                );
            refreshCurrentPage();
        }
    }
}

function addToggleButton($input) {
    if ($input.next('.toggle-visibility').length === 0) {
        const $container = $('<div class="password-input-container"></div>');
        const $toggleButton = $(
            '<button type="button" class="toggle-visibility"><i class="ph ph-eye"></i></button>',
        );

        $toggleButton.on('click', function () {
            togglePasswordVisibility($input, $toggleButton);
        });

        $input.wrap($container);
        $input.after($toggleButton);
    }
}

function togglePasswordVisibility($input, $toggleButton) {
    if ($input.attr('type') === 'password') {
        $input.attr('type', 'text');
        $toggleButton.html('<i class="ph ph-eye-closed"></i>');
    } else {
        $input.attr('type', 'password');
        $toggleButton.html('<i class="ph ph-eye"></i>');
    }
}

$(function () {
    initializeEditors();
    initializePasswordInputs();
    observePasswordInputs();
    setupFormSubmission();
    setupDeleteAction();
    setupIconInput();
    setupFaqModals();
    setupIconContainerHoverEffect();
    setupIconMenu();
    setupTableRowSelection();
});

function initializeEditors() {
    $('.editor-ace').each(function () {
        let editor = ace.edit(this);
        editor.setTheme('ace/theme/solarized_dark');
        editor.session.setMode('ace/mode/json');
    });
}

function initializePasswordInputs() {
    $('input[type="password"]').each(function () {
        addToggleButton($(this));
    });
}

function observePasswordInputs() {
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
}

function setupFormSubmission() {
    $(document).on('submit', '[data-form]', async (ev) => {
        ev.preventDefault();
        let $form = $(ev.currentTarget);

        if (isFormInHiddenTab($form)) {
            console.log('Ignoring submission from a hidden tab.');
            return;
        }

        let path = $form.data('form');
        let form = serializeForm($form);
        if (!form) return;

        let url = `admin/api/${$form.data('page')}/${path}`;
        let method = path === 'edit' ? 'PUT' : 'POST';

        let activeEditorElement = $form.find('[data-editorjs]');
        if (activeEditorElement.length > 0) {
            let editorData = await getEditorData(activeEditorElement);
            form['blocks'] = JSON.stringify(editorData.blocks);
            clearEditorData(activeEditorElement);
        }

        if (ev.target.checkValidity()) {
            sendRequest(form, url, method);
        }
    });
}

async function getEditorData(activeEditorElement) {
    let editorId = activeEditorElement.attr('id');
    let activeEditor = window['editorInstance_' + editorId];
    if (activeEditor) {
        return await activeEditor.save();
    }
    return {};
}

function clearEditorData(activeEditorElement) {
    let editorId = activeEditorElement.attr('id');
    localStorage.removeItem('editorData_' + editorId);
    window.defaultEditorData[editorId] = {};
}

function setupDeleteAction() {
    $(document).on('click', '[data-deleteaction]', async function () {
        let id = $(this).data('deleteaction');
        let path = $(this).data('deletepath');

        if (await asyncConfirm(translate('admin.confirm_delete'))) {
            sendRequest({}, `admin/api/${path}/${id}`, 'DELETE');
        }
    });
}

function setupIconInput() {
    $(document).on('input', '#icon', function () {
        let val = $(this).val().trim();
        $('#icon-output').html(val);
    });

    $(document).on('focus', '#icon', function () {
        const inputOffset = $(this).offset();
        const inputHeight = $(this).outerHeight() + 10;
        $('#icon-menu')
            .css({
                top: inputOffset.top + inputHeight,
                left: inputOffset.left,
            })
            .slideDown(300);
    });

    $(document).on('click', function (event) {
        if (!$(event.target).closest('#icon-menu, #icon').length) {
            $('#icon-menu').slideUp(300);
        }
    });
}

function setupFaqModals() {
    $(document).on('click', '[data-faq]', (e) => {
        e.preventDefault();

        let el = $(e.currentTarget);
        const title = el.data('faq');
        const answer = el.data('faq-content');

        const content = { faq: { answer: answer } };
        Modals.open({ title: title, content });
    });
}

function setupIconContainerHoverEffect() {
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
}

function fetchIcons() {
    $.getJSON(u('admin/api/get-icons'), function (data) {
        icons = data.icons; // Ensure 'icons' is defined globally or in an accessible scope
        updateIconList();
    });
}

function updateIconList() {
    const searchValue = $('#icon-search').val().toLowerCase();
    const style = $('#icon-style').val();
    $('#icon-list').empty();
    icons
        .filter((icon) => icon.includes(searchValue))
        .forEach((icon) => {
            const iconElement = `<i class="${style} ph-${icon}"></i>`;
            $('#icon-list').append(iconElement);
        });
}

function setupIconMenu() {
    const $iconMenu = $(
        '<div id="icon-menu" class="icon-menu"></div>',
    ).appendTo('body');
    const $iconMenuHeader = $(`
        <div class="icon-menu-header">
            <input type="text" id="icon-search" placeholder="" data-translate="def.search" data-translate-attribute="placeholder">
            <div class="form-group">
                <select id="icon-style">
                    <option value="ph-thin">Thin</option>
                    <option value="ph-light">Light</option>
                    <option value="ph" selected>Regular</option>
                    <option value="ph-bold">Bold</option>
                    <option value="ph-duotone">Duotone</option>
                </select>
            </div>
        </div>
    `).appendTo($iconMenu);
    const $iconList = $(
        '<div id="icon-list" class="icon-list"></div>',
    ).appendTo($iconMenu);

    $(document).on('focus', '#icon', function () {
        const inputOffset = $(this).offset();
        const inputHeight = $(this).outerHeight() + 10;
        $iconMenu
            .css({
                top: inputOffset.top + inputHeight,
                left: inputOffset.left,
            })
            .slideDown(300);
    });

    $(document).on('click', function (event) {
        if (!$(event.target).closest('#icon-menu, #icon').length) {
            $iconMenu.slideUp(300);
        }
    });

    $iconMenuHeader.on('input', '#icon-search', updateIconList);
    $iconMenuHeader.on('change', '#icon-style', updateIconList);

    $iconList.on('click', 'i', function () {
        const iconClass = $(this).attr('class');
        $('#icon').val(`<i class="${iconClass}"></i>`).trigger('input');
        $iconMenu.slideUp();
    });

    fetchIcons();
}

function setupTableRowSelection() {
    let isMouseDown = false;
    let isCtrlKey = false;
    let isShiftKey = false;
    let lastSelectedIndex = null;
    let $table, $rows;

    // Event for mouse down on table row
    $(document).on(
        'mousedown',
        'table.selectable.dataTable > tbody > tr',
        function (e) {
            if ($(e.target).closest('td').length) {
                isMouseDown = true;
                isCtrlKey = e.ctrlKey || e.metaKey;
                isShiftKey = e.shiftKey;
                $table = $(this).closest('table');
                $rows = $table.find('tbody > tr');

                const $row = $(this);
                const currentIndex = $rows.index($row);

                handleRowSelection($row, currentIndex);

                lastSelectedIndex = currentIndex;
                updateSelectionInfo();
                return false; // Prevent text selection
            }
        },
    );

    // Event for mouse up to stop selection
    $(document).on('mouseup', function () {
        isMouseDown = false;
    });

    // Event for clicks inside table cells to reset selection
    $(document).on('click', 'table.selectable.dataTable td *', function (e) {
        e.stopPropagation(); // Prevent triggering parent row click
        $('table.selectable.dataTable > tbody > tr').removeClass('selected');
        updateSelectionInfo();
    });

    // Event for clicking outside the table to deselect rows
    $(document).on('click', function (e) {
        if (!$(e.target).closest('table.selectable.dataTable').length) {
            $('table.selectable.dataTable > tbody > tr').removeClass(
                'selected',
            );
            updateSelectionInfo();
        }
    });

    // Event for pressing 'Escape' key to clear selections
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            $('table.selectable.dataTable > tbody > tr').removeClass(
                'selected',
            );
            updateSelectionInfo();
        }
    });

    // Event for deleting selected rows
    $('#delete-rows').on('click', async function () {
        const selectedRows = $(
            'table.selectable.dataTable > tbody > tr.selected',
        );
        const ids = [];
        let path = null;
        let count = 0;

        selectedRows.each(function () {
            let find = $(this).find('.action-button.delete');
            if (find.length) {
                ids.push(find.attr('data-deleteaction'));
                if (!path) {
                    path = find.attr('data-deletepath');
                }
            }
        });

        const callback = (res) => {
            count++;
            if (count === ids.length) {
                refreshCurrentPage();
            }
        };

        if (path && ids.length > 0) {
            if (await asyncConfirm(translate('admin.confirm_delete'))) {
                ids.forEach((id) => {
                    sendRequest(
                        {},
                        `admin/api/${path}/${id}`,
                        'DELETE',
                        callback,
                        false,
                    );
                });
            }
        }
    });

    // Handle row selection logic
    function handleRowSelection($row, currentIndex) {
        if (isCtrlKey) {
            $row.toggleClass('selected');
        } else if (isShiftKey && lastSelectedIndex !== null) {
            const start = Math.min(lastSelectedIndex, currentIndex);
            const end = Math.max(lastSelectedIndex, currentIndex);
            $rows.slice(start, end + 1).addClass('selected');
        } else {
            if ($row.hasClass('selected')) {
                $row.removeClass('selected');
            } else {
                $rows.removeClass('selected');
                $row.addClass('selected');
            }
        }
    }

    // Update selection information display
    function updateSelectionInfo() {
        const selectedRowsCount = $(
            'table.selectable.dataTable > tbody > tr.selected',
        ).length;
        if (selectedRowsCount > 0) {
            $('#selection-info').addClass('opened');
            $('#count-rows > span').text(selectedRowsCount);
        } else {
            $('#selection-info').removeClass('opened');
        }
    }
}

window.defaultEditorData = {};
