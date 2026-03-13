// Global: collect conditions JSON fresh from DOM on every HTMX request
if (!window._ceRequestHooked) {
    window._ceRequestHooked = true;
    document.body.addEventListener('htmx:configRequest', function(e) {
        var editor = document.getElementById('conditions-editor');
        if (!editor) return;

        var groups = [];
        editor.querySelectorAll('.ce-group').forEach(function(grp) {
            var conditions = [];
            grp.querySelectorAll('.ce-row').forEach(function(row) {
                var selects = row.querySelectorAll('select[data-select]');
                var typeSelect = selects[0];
                var opSelect = selects[1];
                var valueInput = row.querySelector('input.input__field');

                var typeVal = '';
                var opVal = '';
                var valVal = '';

                if (typeSelect) {
                    typeVal = typeSelect.tomselect ? typeSelect.tomselect.getValue() : typeSelect.value;
                }
                if (opSelect) {
                    opVal = opSelect.tomselect ? opSelect.tomselect.getValue() : opSelect.value;
                }
                if (valueInput) {
                    valVal = valueInput.value;
                }

                conditions.push({ type: typeVal, operator: opVal, value: valVal });
            });
            if (conditions.length > 0) {
                groups.push(conditions);
            }
        });

        e.detail.parameters['conditions_json'] = JSON.stringify(groups);
    });
}

function initConditionsEditor() {
    var editor = document.getElementById('conditions-editor');
    if (!editor || editor.dataset.ceInit) return;
    editor.dataset.ceInit = '1';

    var config = JSON.parse(editor.dataset.config || '{}');
    var conditionTypes = config.conditionTypes || {};
    var operators = config.operators || {};
    var i18n = config.i18n || {};

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    function selectHtml(name, opts, ph) {
        var html = '<option value="" selected disabled>' + esc(ph) + '</option>';
        Object.keys(opts).forEach(function(k) {
            html += '<option value="' + esc(k) + '">' + esc(opts[k]) + '</option>';
        });
        return '<div class="select-wrapper"><div class="select__field-container" data-controller="select"'
            + ' data-select-placeholder="' + esc(ph) + '" data-select-allow-empty="1">'
            + '<select name="' + esc(name) + '" class="select__field" data-select data-allow-empty="true"'
            + ' data-initial-value="">​' + html + '</select></div></div>';
    }

    function initNewSelects(el) {
        if (!window.Select) return;
        el.querySelectorAll('[data-select]').forEach(function(s) {
            if (!s.tomselect) window.Select.createInstance(s);
        });
    }

    function getMaxGroup() {
        var m = -1;
        editor.querySelectorAll('.ce-group').forEach(function(g) {
            var i = parseInt(g.dataset.group, 10);
            if (i > m) m = i;
        });
        return m;
    }

    function reindex() {
        editor.querySelectorAll('.ce-group').forEach(function(grp, gi) {
            grp.dataset.group = gi;
            grp.querySelectorAll('.ce-row').forEach(function(row, ci) {
                row.dataset.condition = ci;
            });
        });
    }

    function rebuildConnectors() {
        editor.querySelectorAll('.ce-connector, .ce-separator').forEach(function(e) { e.remove(); });

        var groups = editor.querySelectorAll('.ce-group');
        groups.forEach(function(grp, gi) {
            if (gi > 0) {
                var sep = document.createElement('div');
                sep.className = 'ce-separator';
                sep.innerHTML = '<span class="ce-badge ce-or">OR</span>';
                grp.parentNode.insertBefore(sep, grp);
            }
            var rows = grp.querySelectorAll('.ce-row');
            rows.forEach(function(row, ci) {
                if (ci > 0) {
                    var con = document.createElement('div');
                    con.className = 'ce-connector';
                    con.innerHTML = '<span class="ce-badge ce-and">AND</span>';
                    row.parentNode.insertBefore(con, row);
                }
            });
        });

        var empty = editor.querySelector('.ce-empty');
        if (empty && groups.length > 0) empty.remove();
    }

    function makeRow(gi, ci) {
        var row = document.createElement('div');
        row.className = 'ce-row';
        row.dataset.condition = ci;
        row.innerHTML = '<div class="ce-fields">'
            + '<div class="ce-field ce-field-type">' + selectHtml('conditions_' + gi + '_' + ci + '_type', conditionTypes, i18n.typePlaceholder) + '</div>'
            + '<div class="ce-field ce-field-op">' + selectHtml('conditions_' + gi + '_' + ci + '_operator', operators, i18n.operatorPlaceholder) + '</div>'
            + '<div class="ce-field ce-field-val"><div class="input-wrapper"><div class="input__field-container">'
            + '<input type="text" name="conditions_' + gi + '_' + ci + '_value" class="input__field" placeholder="' + esc(i18n.valuePlaceholder) + '" value="">'
            + '</div></div></div></div>'
            + '<div class="ce-actions">'
            + '<button type="button" class="btn btn-outline-primary btn-tiny ce-btn-and" data-tooltip="AND">And</button>'
            + '<button type="button" class="btn btn-outline-warning btn-tiny ce-btn-or" data-tooltip="OR">Or</button>'
            + '<button type="button" class="btn btn-outline-error btn-tiny ce-btn-remove">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 256 256"><path d="M208.49,191.51a12,12,0,0,1-17,17L128,145,64.49,208.49a12,12,0,0,1-17-17L111,128,47.51,64.49a12,12,0,0,1,17-17L128,111l63.51-63.52a12,12,0,0,1,17,17L145,128Z"/></svg>'
            + '</button></div>';
        return row;
    }

    function makeGroup(gi) {
        var grp = document.createElement('div');
        grp.className = 'ce-group';
        grp.dataset.group = gi;
        grp.appendChild(makeRow(gi, 0));
        return grp;
    }

    function addConditionToGroup(grp) {
        var gi = parseInt(grp.dataset.group, 10);
        var ci = grp.querySelectorAll('.ce-row').length;
        var row = makeRow(gi, ci);
        grp.appendChild(row);
        initNewSelects(row);
        rebuildConnectors();
    }

    function addNewGroup() {
        var gi = getMaxGroup() + 1;
        var grp = makeGroup(gi);
        editor.appendChild(grp);
        initNewSelects(grp);
        reindex();
        rebuildConnectors();
    }

    function removeRow(row) {
        var grp = row.closest('.ce-group');
        var rows = grp.querySelectorAll('.ce-row');
        if (rows.length <= 1) {
            var groups = editor.querySelectorAll('.ce-group');
            if (groups.length <= 1) return;
            grp.remove();
        } else {
            row.remove();
        }
        reindex();
        rebuildConnectors();
    }

    editor.addEventListener('click', function(e) {
        var btn;
        if ((btn = e.target.closest('.ce-btn-and'))) {
            addConditionToGroup(btn.closest('.ce-group'));
            return;
        }
        if ((btn = e.target.closest('.ce-btn-or'))) {
            addNewGroup();
            return;
        }
        if ((btn = e.target.closest('.ce-btn-remove'))) {
            removeRow(btn.closest('.ce-row'));
            return;
        }
    });

    var addFirstBtn = document.getElementById('ce-add-first');
    if (addFirstBtn) {
        addFirstBtn.addEventListener('click', function() {
            addNewGroup();
        });
    }
}

// Run immediately if DOM is already ready
if (document.readyState !== 'loading') {
    initConditionsEditor();
} else {
    document.addEventListener('DOMContentLoaded', initConditionsEditor);
}
// Handle HTMX navigations (register once)
if (!window._ceListenersRegistered) {
    window._ceListenersRegistered = true;
    document.addEventListener('htmx:afterSettle', initConditionsEditor);
    document.addEventListener('htmx:afterSwap', initConditionsEditor);
}
