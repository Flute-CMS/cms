Modals.addParser('serviceProviderSettings', (config, modalContent) => {
    let container = document.createElement('div');
    container.classList.add('position-relative', 'row', 'form-group', 'gx-3');

    // Добавляем заголовок для секции настроек
    let header = document.createElement('h3');
    header.innerHTML = translate('admin.modules_list.sp_settings');
    header.classList.add('col-sm-4', 'col-form-label');
    container.appendChild(header);

    let spContainer = document.createElement('div');
    spContainer.classList.add('service-provider', 'col-sm-8');

    const prefixContent = 'Flute\\Modules\\';

    // Функция для создания элемента serviceProvider
    function createServiceProviderElement(spName) {
        let spCont = document.createElement('div');
        spCont.classList.add('sp-container');

        let prefix = document.createElement('span');
        prefix.textContent = prefixContent;
        prefix.classList.add('sp-prefix');
        spCont.appendChild(prefix);

        let input = document.createElement('input');
        input.type = 'text';
        input.classList.add('form-control');
        input.value = spName.startsWith(prefixContent)
            ? spName.substring(14)
            : spName;
        spCont.appendChild(input);

        let deleteButton = document.createElement('i');
        deleteButton.classList.add('ph', 'ph-trash', 'btn-delete-sp');
        deleteButton.onclick = () => {
            spCont.remove();
        };
        spCont.appendChild(deleteButton);

        return spCont;
    }

    // Добавляем существующие serviceProviders
    config.serviceProviders.forEach((sp) => {
        let spElement = createServiceProviderElement(sp);
        spContainer.appendChild(spElement);
    });

    // Кнопка добавления нового serviceProvider
    let addButton = document.createElement('button');
    addButton.innerHTML = translate('def.add');
    addButton.classList.add('btn', 'btn-primary', 'size-s', 'primary');
    addButton.onclick = () => {
        let newSpElement = createServiceProviderElement('');
        spContainer.insertBefore(newSpElement, addButton);
    };

    spContainer.appendChild(addButton);
    container.appendChild(spContainer);

    return container;
});

Modals.addParser('zipUpload', (config, modalContent) => {
    let uploadContainer = document.createElement('div');
    uploadContainer.classList.add(
        'upload-container',
        'position-relative',
        'row',
        'form-group',
        'gx-3',
        'animate__animated',
    );
    let uploadArea = document.createElement('div');
    uploadArea.classList.add('upload-area', 'col-sm-12');
    uploadArea.innerHTML = translate('admin.modules_list.drag');

    let fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = '.zip';
    fileInput.style.display = 'none';
    fileInput.onchange = handleFileSelect;

    // Event listeners for drag-and-drop
    uploadArea.addEventListener('click', () => fileInput.click());
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('drag-over');
    });
    uploadArea.addEventListener('dragleave', () =>
        uploadArea.classList.remove('drag-over'),
    );
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('drag-over');
        handleFileSelect(e.dataTransfer);
    });

    uploadContainer.appendChild(uploadArea);
    uploadContainer.appendChild(fileInput);
    modalContent.appendChild(uploadContainer);

    function handleFileSelect(event) {
        let files = event.files || event.target.files;
        if (files.length > 0) {
            let file = files[0];

            // Check for ZIP file format
            if (
                file.type !== 'application/zip' &&
                file.type !== 'application/x-zip-compressed'
            ) {
                toast({
                    message: 'Invalid file format. Please upload a ZIP file.',
                    type: 'error',
                });
                return;
            }

            // Check for file size (e.g., 10MB limit)
            const maxSize = 10 * 1024 * 1024; // 10MB
            if (file.size > maxSize) {
                toast({
                    message: 'File is too large. Maximum size is 10MB.',
                    type: 'error',
                });
                return;
            }

            processFile(file);
        }
    }

    function processFile(file) {
        let formData = new FormData();
        formData.append('file', file);

        // Example API request
        fetch(u('admin/api/modules/install'), {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'x-csrf-token': csrfToken,
            },
            body: formData,
        })
            .then((response) => response.json())
            .then((data) => {
                if (data?.error) throw new Error(data.error);

                if (data.errors && data.errors.length > 0) {
                    displayErrors(data);
                } else {
                    uploadContainer.style.display = 'none';
                    displayModuleInfo(
                        data.moduleName,
                        data.moduleVersion,
                        data.type,
                    );
                }
            })
            .catch((error) => {
                toast({
                    type: 'error',
                    message: error ?? translate('def.unknown_error'),
                });

                Modals.clear();
            });
    }

    function displayErrors(data) {
        let moduleName = document.createElement('div');
        moduleName.innerHTML = translate('def.name') + ' - ' + data?.moduleName;

        let errorContainer = document.createElement('div');
        errorContainer.classList.add(
            'error-container',
            'd-flex',
            'flex-column',
        );

        data?.errors.forEach((err) => {
            let errorItem = document.createElement('div');
            errorItem.textContent = err;
            errorContainer.appendChild(errorItem);
        });

        modalContent.appendChild(moduleName);
        modalContent.appendChild(errorContainer);
    }

    function displayModuleInfo(name, version, type = 'install') {
        let infoContainer = document.createElement('div');
        infoContainer.classList.add('info-container');

        let moduleName = document.createElement('p');
        moduleName.innerHTML = translate('admin.modules_list.module_name', {
            name,
        });
        infoContainer.appendChild(moduleName);

        let moduleVersion = document.createElement('p');
        moduleVersion.innerHTML = translate('admin.modules_list.version', {
            version,
        });
        infoContainer.appendChild(moduleVersion);

        let installButton = document.createElement('button');
        installButton.innerHTML =
            type === 'install'
                ? translate('def.install')
                : translate('def.update');
        installButton.classList.add('btn', 'primary', 'size-s');
        installButton.addEventListener('click', () =>
            installModule(name, type),
        );
        infoContainer.appendChild(installButton);

        modalContent.appendChild(infoContainer);
    }

    function installModule(moduleName, type = 'install') {
        fetch(u(`admin/api/modules/${type}/${moduleName}`), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'x-csrf-token': csrfToken,
            },
        })
            .then((response) => response.json())
            .then((data) => {
                if (data?.error) throw new Error(data.error);

                toast({
                    type: 'success',
                    message: data.success ?? translate('def.success'),
                });

                Modals.clear();

                refreshCurrentPage();
            })
            .catch((error) => {
                toast({
                    type: 'error',
                    message: error ?? translate('def.unknown_error'),
                });
            });
    }

    uploadContainer.appendChild(uploadArea);
    uploadContainer.appendChild(fileInput);

    return uploadContainer;
});

$(document).on('click', '[data-settingsmodule]', (e) => {
    let el = $(e.currentTarget);
    let key = el.data('key');

    Modals.open({
        closeOnBackground: false,
        title: translate('admin.modules_list.manage', { key }),
        content: {
            serviceProviderSettings: {
                serviceProviders: el.data('settingsmodule').providers,
            },
        },
        buttons: [
            {
                text: translate('def.save'),
                class: 'primary',
                callback: (modalInstance) => {
                    let serviceProviders = [];
                    let allValid = true;

                    $('.service-provider input').each(function () {
                        let value = $(this).val().trim();
                        if (value === '') {
                            allValid = false;
                            $(this).addClass('has-error');
                        } else {
                            $(this).removeClass('has-error');
                            serviceProviders.push(value);
                        }
                    });

                    if (allValid) {
                        $.ajax({
                            url: u(`admin/api/modules/${el.data('key')}`),
                            type: 'PUT',
                            data: {
                                providers: serviceProviders,
                            },
                            async: false,
                            success: function (response) {
                                modalInstance.clear();

                                toast({
                                    type: 'success',
                                    message:
                                        response.success ??
                                        translate('def.success'),
                                });
                            },
                            error: function (xhr, status, error) {
                                toast({
                                    type: 'error',
                                    message:
                                        xhr?.responseJSON?.error ??
                                        translate('def.unknown_error'),
                                });
                            },
                        });
                    } else {
                        toast({
                            type: 'error',
                            message: translate('validator.form_invalid'),
                        });
                    }

                    return false;
                },
            },
            {
                text: translate('def.cancel'),
                class: 'error',
                callback: (modalInstance) => {
                    modalInstance.clear();
                },
            },
        ],
    });
});

$(function () {
    let csrfToken = $('meta[name="csrf-token"]').attr('content');

    $(document).on('click', '[data-moduleinstall]', (e) => {
        Modals.open({
            title: translate('admin.modules_list.module_install'),
            closeOnBackground: false,
            content: {
                zipUpload: {},
            },
            buttons: [],
        });
    });

    // Handle delete module action
    $(document).on(
        'click',
        '.module-action-buttons .action-button.delete',
        async function () {
            let moduleId = $(this).data('deletemodule');
            if (
                await asyncConfirm(
                    translate('admin.modules_list.confirm_delete'),
                )
            )
                sendRequest({}, u('admin/api/modules/' + moduleId), 'DELETE');
        },
    );

    // Handle install module action
    $(document).on(
        'click',
        '.module-action-buttons .action-button.install',
        async function () {
            let moduleId = $(this).data('installmodule');
            if (
                await asyncConfirm(
                    translate('admin.modules_list.confirm_install'),
                    null,
                    translate('def.install'),
                    null,
                    'primary',
                )
            )
                sendRequest(
                    {},
                    u('admin/api/modules/install/' + moduleId),
                    'POST',
                );
        },
    );

    // Handle disable module action
    $(document).on(
        'click',
        '.module-action-buttons .action-button.disable',
        function () {
            let moduleId = $(this).data('disablemodule');
            sendRequest({}, u('admin/api/modules/disable/' + moduleId), 'POST');
        },
    );

    // Handle enable module action
    $(document).on(
        'click',
        '.module-action-buttons .action-button.activate',
        function () {
            let moduleId = $(this).data('activatemodule');
            sendRequest({}, u('admin/api/modules/enable/' + moduleId), 'POST');
        },
    );

    $(document).on(
        'click',
        '.module-action-buttons .action-button.update',
        async function () {
            let moduleId = $(this).data('updatemodule');
            if (
                await asyncConfirm(
                    translate('admin.modules_list.confirm_update'),
                    null,
                    translate('def.update'),
                    null,
                    'primary',
                )
            )
                sendRequest(
                    {},
                    u('admin/api/modules/update/' + moduleId),
                    'POST',
                );
        },
    );
});
