$(document).ready(function () {
    let paramIndexes = {};

    $('.translations-container').each(function () {
        const lang = $(this).attr('id');
        paramIndexes[lang] = 0;
    });

    $(document).on('click', '.addParam', function () {
        const lang = $(this).data('lang');
        paramIndexes[lang]++;
        appendParamFields(lang, paramIndexes[lang]);
    });

    function appendParamFields(lang, index, key = '', value = '') {
        $(`#${lang} .parametersContainer`).append(`
            <div class="param-group" id="param-group-${lang}-${index}">
                <input type="text" name="paramNames[]" class="form-control" placeholder="Key" value="${key}" required>
                <input type="text" name="paramValues[]" class="form-control" placeholder="Value" value="${value}" required>
                <button type="button" class="removeParam btn size-s error" data-lang="${lang}" data-id="${index}">${translate('def.delete')}</button>
            </div>
        `);
    }

    $(document).on('click', '.removeParam', function () {
        let lang = $(this).data('lang');
        let id = $(this).data('id');
        $(`#param-group-${lang}-${id}`).remove();
    });

    $('.translations a').on('click', function (e) {
        e.preventDefault();

        let selectedLang = $(this).data('lang');

        $('.translations a').not(this).removeClass('active');
        $(this).addClass('active');

        $('.translations-container > div').hide();
        $('#' + selectedLang).show();
    });

    $('[data-save]').on('click', function () {
        let allTranslations = {};

        $('.translations-container > div').each(function () {
            let lang = $(this).attr('id');
            allTranslations[lang] = [];

            $(this)
                .find('.param-group')
                .each(function () {
                    let key = $(this)
                        .find('input[name="paramNames[]"]')
                        .val()
                        .trim();
                    let value = $(this)
                        .find('input[name="paramValues[]"]')
                        .val()
                        .trim();

                    if (key && value) {
                        allTranslations[lang].push({ key: key, value: value });
                    }
                });
        });

        sendRequest(JSON.stringify(allTranslations), `admin/api/translate/change`);
    });
});
