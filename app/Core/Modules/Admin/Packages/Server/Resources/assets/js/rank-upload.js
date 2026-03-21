initRankUpload();

document.body.addEventListener('htmx:afterSettle', initRankUpload);

function initRankUpload() {
    var zone = document.querySelector('[data-rank-dropzone]');
    if (!zone || zone._ru) return;
    zone._ru = true;

    var input = zone.querySelector('[data-rank-file-input]');
    var btn = zone.querySelector('[data-rank-upload-btn]');
    var label = zone.querySelector('[data-rank-dropzone-text]');
    var hint = zone.querySelector('[data-rank-dropzone-hint]');
    if (!input || !btn || !label || !hint) return;

    var btnLabel = btn.querySelector('[data-rank-btn-label]');
    var iconWrap = zone.querySelector('[data-rank-icon]');
    var defaults = {
        text: label.textContent,
        hint: hint.textContent,
        btn: btnLabel.innerHTML,
        icon: iconWrap.innerHTML,
    };

    function formatSize(b) {
        if (b < 1024) return b + ' B';
        if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
        return (b / 1048576).toFixed(1) + ' MB';
    }

    function reset() {
        label.textContent = defaults.text;
        hint.textContent = defaults.hint;
        btnLabel.innerHTML = defaults.btn;
        iconWrap.innerHTML = defaults.icon;
        btn.disabled = true;
        zone.classList.remove('is-uploading');
    }

    function showToasts(xhr) {
        var h = xhr.getResponseHeader('X-Toasts');
        if (!h) return;
        try {
            JSON.parse(h).forEach(function (t) {
                if (typeof displayToast === 'function') displayToast(t);
                else if (typeof notyf !== 'undefined') notyf.open({ type: t.type || 'info', message: t.message || '' });
            });
        } catch (e) {}
    }

    function swapResponse(xhr, yoyoEl, componentId) {
        var html = xhr.responseText.trim();
        if (!html) return false;

        var temp = document.createElement('div');
        temp.innerHTML = html;
        var el = temp.querySelector('#' + CSS.escape(componentId)) || temp.firstElementChild;
        if (!el) return false;

        yoyoEl.outerHTML = el.outerHTML;
        var newEl = document.getElementById(componentId);
        if (newEl) {
            htmx.process(newEl);
            if (window.Select) window.Select.init(newEl);
        }
        initRankUpload();
        return true;
    }

    function upload() {
        var yoyoEl = zone.closest('[yoyo\\:name]');
        if (!yoyoEl || !window.Yoyo) return;

        var name = yoyoEl.getAttribute('yoyo:name') || '';
        var id = yoyoEl.id || '';
        var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

        var fd = new FormData();
        fd.append('ranks_archive', input.files[0]);
        fd.append('component', name + '/uploadRankPack');
        fd.append('yoyo-id', id);

        btn.disabled = true;
        zone.classList.add('is-uploading');
        iconWrap.innerHTML = '<span class="rank-upload__spinner"></span>';
        btnLabel.textContent = '\u2026';

        var xhr = new XMLHttpRequest();
        xhr.open('POST', Yoyo.url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('HX-Request', 'true');
        xhr.setRequestHeader('X-Csrf-Token', csrf);
        xhr.setRequestHeader('HX-Target', id);

        xhr.onload = function () {
            var tk = xhr.getResponseHeader('X-CSRF-Token');
            if (tk) {
                var m = document.querySelector('meta[name="csrf-token"]');
                if (m) m.setAttribute('content', tk);
            }

            showToasts(xhr);

            if (xhr.status >= 200 && xhr.status < 300) {
                if (!swapResponse(xhr, yoyoEl, id)) {
                    window.location.reload();
                }
            } else {
                reset();
            }
        };

        xhr.onerror = function () {
            reset();
        };

        xhr.send(fd);
    }

    input.addEventListener('change', function () {
        if (input.files.length) {
            label.textContent = input.files[0].name;
            hint.textContent = formatSize(input.files[0].size);
            btn.disabled = false;
        } else {
            reset();
        }
    });

    btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (!input.files.length || btn.disabled) return;
        upload();
    });

    zone.addEventListener('dragover', function (e) {
        e.preventDefault();
        zone.classList.add('is-dragover');
    });

    zone.addEventListener('dragleave', function () {
        zone.classList.remove('is-dragover');
    });

    zone.addEventListener('drop', function (e) {
        e.preventDefault();
        zone.classList.remove('is-dragover');
        if (e.dataTransfer.files.length) {
            input.files = e.dataTransfer.files;
            input.dispatchEvent(new Event('change'));
        }
    });
}
