window.FluteRichText = window.FluteRichText || {};

window.FluteRichText.createExtensions = function (textarea, T) {
    return [
        T.StarterKit.configure({ heading: { levels: [1, 2, 3, 4, 5, 6] } }),
        T.Underline,
        T.Superscript,
        T.Subscript,
        T.TextAlign.configure({ types: ['heading', 'paragraph'] }),
        T.Link.configure({
            openOnClick: false,
            HTMLAttributes: { rel: 'noopener noreferrer nofollow' },
        }),
        T.Image.configure({
            allowBase64: false,
            HTMLAttributes: { loading: 'lazy' },
            resize: {
                enabled: true,
                alwaysPreserveAspectRatio: true,
                minWidth: 50,
                minHeight: 50,
            },
        }),
        T.Table.configure({ resizable: true }),
        T.TableRow,
        T.TableCell,
        T.TableHeader,
        T.TaskList,
        T.TaskItem.configure({ nested: true }),
        T.Youtube.configure({
            width: 640,
            height: 360,
            HTMLAttributes: { class: 'youtube-embed' },
        }),
        T.TextStyle,
        T.Color,
        T.Highlight.configure({ multicolor: true }),
        T.Focus.configure({ className: 'has-focus', mode: 'deepest' }),
        T.Placeholder.configure({
            placeholder: textarea.getAttribute('placeholder') || '',
        }),
        T.CharacterCount,
    ];
};
