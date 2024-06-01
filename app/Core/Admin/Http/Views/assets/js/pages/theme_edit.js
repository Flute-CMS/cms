function updateValue($element, id, value) {
    let unit = id === 'transition' ? 's' : 'px';
    $element.find('#' + id + '-value').text(value + unit);
    $('#theme-editor').css('--' + id, value + unit);

    // Обновление --range у родительского элемента с классом range-control
    let percentage =
        ((value - parseFloat($element.find('#' + id).attr('min'))) /
            (parseFloat($element.find('#' + id).attr('max')) -
                parseFloat($element.find('#' + id).attr('min')))) *
        100;
    $element.css('--range', percentage + '%');
}

function initRange($element, id) {
    let range = $element.find('input[type="range"]');
    let value = range.val();
    console.log($element, id);
    updateValue($element, id, value);
}

function parseColors() {
    var editor = document.querySelector('#theme-editor');

    if (!editor) return;

    const editorId = editor.getAttribute('id');
    const pickrInstances = {};

    // Initialize Pickr for each color input
    editor.querySelectorAll('.color-picker').forEach(function (pickerElement) {
        const colorVariable = pickerElement
            .getAttribute('id')
            .replace('-picker', '');
        const initialColor = getComputedStyle(document.documentElement)
            .getPropertyValue(`--${colorVariable}`)
            .trim();

        const pickr = Pickr.create({
            el: `#${pickerElement.getAttribute('id')}`,
            theme: 'classic',
            default: initialColor,
            comparison: false,
            swatches: [
                'rgba(244, 67, 54, 1)',
                'rgba(233, 30, 99, 0.95)',
                'rgba(156, 39, 176, 0.9)',
                'rgba(103, 58, 183, 0.85)',
                'rgba(63, 81, 181, 0.8)',
                'rgba(33, 150, 243, 0.75)',
                'rgba(3, 169, 244, 0.7)',
                'rgba(0, 188, 212, 0.7)',
                'rgba(0, 150, 136, 0.75)',
                'rgba(76, 175, 80, 0.8)',
                'rgba(139, 195, 74, 0.85)',
                'rgba(205, 220, 57, 0.9)',
                'rgba(255, 235, 59, 0.95)',
                'rgba(255, 193, 7, 1)',
            ],
            components: {
                preview: true,
                opacity: true,
                hue: true,
                interaction: {
                    hex: true,
                    rgba: true,
                    hsla: true,
                    hsva: true,
                    cmyk: true,
                    input: true,
                    clear: true,
                },
            },
        });

        pickrInstances[colorVariable] = pickr;

        pickr.on('change', (color, source, instance) => {
            const rgbaColor = color.toRGBA().toString();
            document
                .querySelector(`#${editorId} .theme-preview`)
                .style.setProperty(`--${colorVariable}`, rgbaColor);

            pickerElement.value = rgbaColor;
        });
    });

    window.pickrInstances = pickrInstances;
}

function addUnitsToProperties(settings) {
    const unitProperties = {
        transition: 's',
        'border-radius': 'px',
        'border-radius-el': 'px',
    };

    for (const [property, unit] of Object.entries(unitProperties)) {
        if (
            settings.hasOwnProperty(property) &&
            !settings[property].endsWith(unit)
        ) {
            settings[property] += unit;
        }
    }
}

$(function () {
    parseColors();

    $(document).on('input', '#transition', function () {
        let $this = $(this).closest('.range-control');
        updateValue($this, 'transition', $(this).val());
    });

    $('.range-control').each(function (e) {
        let $this = $(this).find('input[type="range"]');
        initRange($(this), $this.attr('id'));
    });

    document
        .querySelector('.chrome-tabs')
        .addEventListener('contentRender', () => {
            $('.range-control').each(function (e) {
                let $this = $(this).find('input[type="range"]');
                initRange($(this), $this.attr('id'));
            });
            parseColors();
        });

    // Button click handler to save theme settings
    $(document).on('click', '[data-savetheme]', (e) => {
        const id = $(e.currentTarget).data('savetheme');
        const themeSettings = {};

        // Get values from input elements
        document
            .querySelectorAll('#theme-editor input')
            .forEach(function (inputElement) {
                themeSettings[inputElement.name] = inputElement.value;
            });

        // Get values from color picker elements
        document
            .querySelectorAll('#theme-editor .color-picker')
            .forEach(function (pickerElement) {
                const colorVariable = pickerElement
                    .getAttribute('id')
                    .replace('-picker', '');
                themeSettings[colorVariable] = pickerElement.value;
            });

        // Get values from Pickr instances
        for (const [key, pickr] of Object.entries(window.pickrInstances)) {
            const color = pickr.getColor().toRGBA().toString();
            themeSettings[key] = color;
        }

        addUnitsToProperties(themeSettings);

        sendRequest(
            { colors: themeSettings },
            `admin/api/themes/variables/${id}`,
            'PUT',
        );
    });
});
