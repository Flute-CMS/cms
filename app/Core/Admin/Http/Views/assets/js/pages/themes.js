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
    uploadArea.innerHTML = translate('admin.themes_list.drag');

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
        fetch(u('admin/api/themes/install'), {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                "x-csrf-token": csrfToken 
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
                    displayModuleInfo(data.themeName, data.themeVersion);
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
        let themeName = document.createElement('div');
        themeName.innerHTML = translate('def.name') + ' - ' + data?.themeName;

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

        modalContent.appendChild(themeName);
        modalContent.appendChild(errorContainer);
    }

    function displayModuleInfo(name, version) {
        let infoContainer = document.createElement('div');
        infoContainer.classList.add('info-container');

        let themeName = document.createElement('p');
        themeName.innerHTML = translate('admin.themes_list.theme_name', {
            name,
        });
        infoContainer.appendChild(themeName);

        let installButton = document.createElement('button');
        installButton.innerHTML = translate('def.install');
        installButton.classList.add('btn', 'primary', 'size-s');
        installButton.addEventListener('click', () => installModule(name));
        infoContainer.appendChild(installButton);

        modalContent.appendChild(infoContainer);
    }

    function installModule(themeName) {
        fetch(u(`admin/api/themes/install/${themeName}`), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
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

                setTimeout(() => window.location.reload(), 1000);
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

Modals.addParser('themeSettings', (config, modalContent) => {
    let container = document.createElement('div');
    container.classList.add('theme-settings-container');

    config.settings.forEach((setting) => {
        // Create the row container
        let rowContainer = document.createElement('div');
        rowContainer.classList.add('position-relative', 'row', 'form-group');

        // Create the label column
        let labelCol = document.createElement('div');
        labelCol.classList.add('col-sm-5', 'col-form-label');
        if (setting.required) {
            labelCol.classList.add('required');
        }

        let label = document.createElement('label');
        label.innerHTML = translate(setting.name);
        labelCol.appendChild(label);

        if (setting.description) {
            let small = document.createElement('small');
            small.classList.add('form-text', 'text-muted');
            small.innerHTML = translate(setting.description);
            labelCol.appendChild(small);
        }

        rowContainer.appendChild(labelCol);

        // Create the input column
        let inputCol = document.createElement('div');
        inputCol.classList.add('col-sm-7');

        let input = document.createElement('input');
        input.type = 'text';
        input.classList.add('form-control');
        input.name = setting.name;
        input.id = setting.id;
        input.key = setting.key;
        input.value = setting.value;

        if (setting.required) {
            input.required = true;
        }

        inputCol.appendChild(input);
        rowContainer.appendChild(inputCol);

        container.appendChild(rowContainer);
    });

    return container;
});

$(document).on('click', '[data-settingstheme]', (e) => {
    let modal = Modals.open({
        title: translate('admin.themes_list.theme_settings'),
        content: {
            themeSettings: {
                settings: $(e.currentTarget).data('settingstheme'),
            },
        },
        buttons: [
            {
                text: translate('def.save'),
                class: 'primary',
                callback: (modalInstance) => saveThemeSettings(modalInstance, $(e.currentTarget).data('key')),
            },
            {
                text: translate('def.close'),
                callback: () => Modals.clear(),
            },
        ],
    });
});

function saveThemeSettings(modalInstance, theme) {
    let settings = {};
    document
        .querySelectorAll('.theme-settings-container input')
        .forEach((input) => {
            settings[input.key] = input.value;
        });

    fetch(u('admin/api/themes/' + theme), {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            "x-csrf-token": csrfToken 
        },
        body: JSON.stringify({settings: settings}),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data?.error) throw new Error(data.error);

            toast({
                type: 'success',
                message: data.success ?? translate('def.success'),
            });

            Modals.clear();
        })
        .catch((error) => {
            toast({
                type: 'error',
                message:
                    error ??
                    translate('def.unknown_error'),
            });
        });
}

$(document).ready(function () {
    function ajaxModuleAction(url, method, data = {}) {
        $.ajax({
            url: url,
            type: method,
            data: {...data, ...{
                "x-csrf-token": csrfToken
            }},
            success: function (response) {
                toast({
                    type: 'success',
                    message: response.success ?? translate('def.success'),
                });

                setTimeout(() => window.location.reload(), 1000);
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
    }

    $(document).on('click', '[data-install]', (e) => {
        Modals.open({
            title: translate('admin.themes_list.theme_install'),
            closeOnBackground: false,
            content: {
                zipUpload: {},
            },
            buttons: [],
        });
    });

    // Handle delete theme action
    $(document).on('click', '.action-button.delete', function () {
        let themeId = $(this).data('deletetheme');
        if (confirm(translate('admin.themes_list.confirm_delete')))
            ajaxModuleAction(u('admin/api/themes/' + themeId), 'DELETE');
    });

    // Handle install theme action
    $(document).on('click', '.action-button.install', function () {
        let themeId = $(this).data('installtheme');
        if (confirm(translate('admin.themes_list.confirm_install')))
            ajaxModuleAction(u('admin/api/themes/install/' + themeId), 'POST');
    });

    // Handle disable theme action
    $(document).on('click', '.action-button.disable', function () {
        let themeId = $(this).data('disabletheme');
        ajaxModuleAction(u('admin/api/themes/disable/' + themeId), 'POST');
    });

    // Handle enable theme action
    $(document).on('click', '.action-button.activate', function () {
        let themeId = $(this).data('activatetheme');
        ajaxModuleAction(u('admin/api/themes/enable/' + themeId), 'POST');
    });
});
