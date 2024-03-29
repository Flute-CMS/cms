var editor = ace.edit('editor');

var unformattedContent = editor.getSession().getValue();
var formattedContent = js_beautify(unformattedContent, {
    indent_size: 4,
    space_in_empty_paren: true,
});
editor.getSession().setValue(formattedContent);

editor.setTheme('ace/theme/solarized_dark');
editor.session.setMode('ace/mode/json');

$(document).on('submit', '#add, #edit', (ev) => {
    let $form = $(ev.currentTarget);

    ev.preventDefault();
    let path = $form.attr('id'),
        form = serializeForm($form);

    let url = `admin/api/socials/${path}`,
        method = 'POST';

    if (path === 'edit') {
        url = `admin/api/socials/${form.id}`;
        method = 'PUT';
    }

    if (ev.target.checkValidity()) {
        sendRequest(
            {
                ...form,
                ...{
                    settings: editor.getValue(),
                },
            },
            url,
            method,
        );
    }
});
