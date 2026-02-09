window.FluteRichText = window.FluteRichText || {};

(function () {
    var Icons = {};
    var tpl = document.getElementById('richtext-editor-icons');

    if (tpl) {
        tpl.content.querySelectorAll('[data-icon]').forEach(function (el) {
            Icons[el.dataset.icon] = el.innerHTML;
        });
    }

    window.FluteRichText.Icons = Icons;

    window.FluteRichText.icon = function (name) {
        return Icons[name] || '';
    };
})();
