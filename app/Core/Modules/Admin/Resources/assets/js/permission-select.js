window.renderPermissionOption = function (option) {
    var desc = option.description || (option.data && option.data.description) || '';
    var label = option.label || option.text || '';

    var el = document.createElement('div');
    el.className = 'fs-option-row';
    el.textContent = label;

    if (desc && desc !== 'permissions.' + label) {
        el.setAttribute('data-tooltip', desc);
        el.setAttribute('data-tooltip-pos', 'left');
    }

    return el;
};
