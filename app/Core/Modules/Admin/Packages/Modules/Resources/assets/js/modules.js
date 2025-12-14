console.log('Modules Manager Initialized');

initModuleUploader();

document.addEventListener('htmx:afterSwap', function () {
    setTimeout(initModuleUploader, 500);
});

function initModuleUploader() {
    setupGlobalDragDrop();
}

function setupGlobalDragDrop() {
    const overlay = document.getElementById('dropzone-overlay');
    const fileInput = document.getElementById('module-file-input');

    if (!overlay || !fileInput) return;

    let dragCounter = 0;

    document.addEventListener('dragenter', function (e) {
        e.preventDefault();
        dragCounter++;

        if (
            e.dataTransfer.types &&
            e.dataTransfer.types.indexOf('Files') !== -1
        ) {
            overlay.classList.add('active');
        }
    });

    document.addEventListener('dragover', function (e) {
        e.preventDefault();
        const overlayContent = overlay.querySelector(
            '.dropzone-overlay__content',
        );

        if (overlayContent && isEventOverElement(e, overlayContent)) {
            overlayContent.classList.add('drag-active');
        } else if (overlayContent) {
            overlayContent.classList.remove('drag-active');
        }
    });

    document.addEventListener('dragleave', function (e) {
        e.preventDefault();
        dragCounter--;

        if (dragCounter <= 0) {
            dragCounter = 0;
            hideDropZone();
        }
    });

    document.addEventListener('drop', function (e) {
        e.preventDefault();
        dragCounter = 0;

        if (e.dataTransfer.files.length) {
            const file = e.dataTransfer.files[0];
            validateAndUploadFile(file);
        }

        hideDropZone();
    });

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) {
            hideDropZone();
        }
    });

    fileInput.addEventListener('change', function (e) {
        if (e.target.files.length) {
            validateAndUploadFile(e.target.files[0]);
        }
    });

    const overlayContent = overlay.querySelector('.dropzone-overlay__content');
    if (overlayContent) {
        overlayContent.addEventListener('dragenter', function (e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('drag-active');
        });

        overlayContent.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('drag-active');
        });

        overlayContent.addEventListener('dragleave', function (e) {
            e.preventDefault();
            e.stopPropagation();

            if (!this.contains(e.relatedTarget)) {
                this.classList.remove('drag-active');
            }
        });

        overlayContent.addEventListener('drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('drag-active');

            if (e.dataTransfer.files.length) {
                const file = e.dataTransfer.files[0];
                validateAndUploadFile(file);
            }
        });
    }
}

function hideDropZone() {
    const overlay = document.getElementById('dropzone-overlay');
    if (!overlay) return;

    overlay.classList.remove('active');

    const overlayContent = overlay.querySelector('.dropzone-overlay__content');
    if (overlayContent) {
        overlayContent.classList.remove('drag-active');
    }

    resetUploader();
}

function validateAndUploadFile(file) {
    if (!isZipFile(file)) {
        showNotyfError(translate('admin-modules.dropzone.errors.invalid_file'));
        return;
    }

    if (file.size > 50 * 1024 * 1024) {
        showNotyfError(
            translate('admin-modules.dropzone.errors.file_too_large'),
        );
        return;
    }

    uploadFile(file);
}

function uploadFile(file) {
    showUploadProgress(file);

    const formData = new FormData();
    formData.append('module_archive', file);

    const token = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    const xhr = new XMLHttpRequest();
    xhr.open('POST', u('admin/modules/install'));

    if (token) {
        xhr.setRequestHeader('X-CSRF-Token', token);
        formData.append('_csrf_token', token);
    }
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    xhr.upload.addEventListener('progress', function (e) {
        if (e.lengthComputable) {
            updateProgress(Math.round((e.loaded / e.total) * 100));
        }
    });

    xhr.addEventListener('load', function () {
        try {
            const response = JSON.parse(xhr.responseText);

            if (xhr.status >= 200 && xhr.status < 300 && response.success) {
                hideDropZone();

                showNotyfSuccess(
                    response.message ||
                        translate('admin-modules.messages.installed'),
                );

                setTimeout(() => {
                    refreshModulesList();
                    if (typeof window.refreshAdminSidebar === 'function') {
                        window.refreshAdminSidebar();
                    }
                }, 1000);
            } else {
                showNotyfError(
                    response.error ||
                        translate('admin-modules.dropzone.errors.unknown'),
                );
            }
        } catch (e) {
            showNotyfError(translate('admin-modules.dropzone.errors.unknown'));
        }
    });

    xhr.addEventListener('error', function (e) {
        showNotyfError(translate('admin-modules.dropzone.errors.network'));
    });

    xhr.addEventListener('timeout', function () {
        showNotyfError(translate('admin-modules.dropzone.errors.timeout'));
    });

    xhr.timeout = 120000;

    xhr.send(formData);
}

function showUploadProgress(file) {
    const overlay = document.getElementById('dropzone-overlay');
    if (!overlay) return;

    overlay.classList.add('active');

    const initialView = overlay.querySelector('.upload-initial');
    const progressView = overlay.querySelector('.upload-progress-view');

    if (initialView && progressView) {
        initialView.style.display = 'none';
        progressView.style.display = 'block';
    }

    const fileNameElement = overlay.querySelector('#upload-file-name');
    if (fileNameElement) {
        fileNameElement.textContent = file.name;
    }

    updateProgress(0);
}

function updateProgress(percent) {
    const overlay = document.getElementById('dropzone-overlay');
    if (!overlay) return;

    const percentElement = overlay.querySelector('#upload-progress-percent');
    const progressBar = overlay.querySelector('#upload-progress-bar');

    if (percentElement) {
        percentElement.textContent = percent + '%';
    }

    if (progressBar) {
        progressBar.style.width = percent + '%';
    }
}

function resetUploader() {
    const overlay = document.getElementById('dropzone-overlay');
    if (!overlay) return;

    const initialView = overlay.querySelector('.upload-initial');
    const progressView = overlay.querySelector('.upload-progress-view');

    if (initialView && progressView) {
        initialView.style.display = 'block';
        progressView.style.display = 'none';
    }

    const fileInput = document.getElementById('module-file-input');
    if (fileInput) {
        fileInput.value = '';
    }
}

function refreshModulesList() {
    const refreshButton = document.querySelector('[hx-post="refreshModules"]');
    if (refreshButton) {
        refreshButton.click();
    }
}

function isZipFile(file) {
    if (!file) return false;

    const fileName = file.name ? file.name.toLowerCase() : '';
    const hasZipExtension = fileName.endsWith('.zip');

    const isMimeZip =
        file.type === 'application/zip' ||
        file.type === 'application/x-zip-compressed' ||
        file.type === 'application/x-zip' ||
        file.type === 'application/octet-stream';

    return hasZipExtension && (isMimeZip || file.type === '');
}

function isEventOverElement(event, element) {
    const rect = element.getBoundingClientRect();
    const x = event.clientX;
    const y = event.clientY;

    return (
        x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom
    );
}

function showNotyfSuccess(message) {
    if (window.notyf) {
        window.notyf.success(message);
    } else {
        alert(message);
    }
}

function showNotyfError(message) {
    if (window.notyf) {
        window.notyf.error(message);
    } else {
        alert(message);
    }
}
