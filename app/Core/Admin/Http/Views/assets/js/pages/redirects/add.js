$(function () {
    $(document).on('change', '.field', function () {
        var key = $(this).val().toLowerCase();
        var description = translate(`admin.redirects.rules.${key}`);
        $(this)
            .closest('.condition-container')
            .find('.input-with-desc p')
            .remove();
        $(this)
            .closest('.condition-container')
            .find('.input-with-desc')
            .append(`<p>${description}</p>`);
    });

    $(document).on('submit', '#redirectAdd, #redirectEdit', (ev) => {
        let $form = $(ev.currentTarget);

        ev.preventDefault();
        let path = $form.attr('id') === 'redirectAdd' ? 'add' : 'edit',
            form = serializeForm($form);

        let url = `admin/api/redirects/${path}`,
            method = 'POST';

        if (path === 'edit') {
            url = `admin/api/redirects/${form.id}`;
            method = 'PUT';
        }

        form['conditions'] = serializeConditions();

        if (ev.target.checkValidity()) {
            sendRequest(form, url, method);
        }
    });

    function serializeConditions() {
        var groups = [];
        $('.conditions')
            .children()
            .each(function () {
                var group = $(this);
                if (group.hasClass('condition-group')) {
                    var conditions = [];
                    group.find('.condition').each(function () {
                        var field = $(this).find('.field').val();
                        var operator = $(this).find('.operator').val();
                        var value = $(this).find('.value').val();
                        conditions.push({
                            field,
                            operator,
                            value,
                        });
                    });
                    groups.push(conditions);
                } else {
                    var field = $(this).find('.field').val();
                    var operator = $(this).find('.operator').val();
                    var value = $(this).find('.value').val();
                    groups.push([{ field, operator, value }]);
                }
            });
        return groups;
    }

    $(document).on('click', '[data-redirect-save]', function (e) {
        var conditionsJson = serializeConditions();
        console.log(JSON.parse(conditionsJson));

        e.stopPropagation();
        e.preventDefault();
    });

    $(document).on('click', '.btn-and', function () {
        var parentCondition = $(this).closest('.condition');
        var newCondition = $(`
          <div class="condition condition-and">
            <div class="condition-container">
                <div class="field-container">
                    <div class="label-and">
                        <div class="line"></div>
                        <div class="wordwrapper">
                            <div class="word" data-translate="def.and"></div>
                        </div>
                    </div>
                    <select class="field">
                        <option value="ip">IP Address</option>
                        <option value="cookie">Cookie</option>
                        <option value="referer">Referer</option>
                        <option value="request_method">Request Method</option>
                        <option value="user_agent">User Agent</option>
                        <option value="header">Header</option>
                        <option value="lang">${translate(
                            'admin.redirects.lang',
                        )}</option>
                    </select>
                </div>
                <select class="operator">
                    <option value="equals">${translate(
                        'admin.redirects.equals',
                    )}</option>
                    <option value="not_equals">${translate(
                        'admin.redirects.not_equals',
                    )}</option>
                    <option value="contains">${translate(
                        'admin.redirects.contains',
                    )}</option>
                    <option value="not_contains">${translate(
                        'admin.redirects.not_contains',
                    )}</option>
                </select>
                <div>
                    <div class="input-with-desc">
                        <input type="text" class="value" placeholder="${translate(
                            'def.enter_value',
                        )}" required>
                        <p>${translate('admin.redirects.rules.ip')}</p>
                    </div>
                    <button type="button" class="btn size-s outline btn-and" data-translate="def.and"></button>
                </div>
                <button type="button" class="btn size-s outline error btn-remove"><i class="ph ph-x"></i></button>
            </div>
          </div>
        `);

        var group = parentCondition.closest('.condition-group');
        if (!group.length) {
            group = $('<div class="condition-group"></div>').appendTo(
                '.conditions',
            );
            parentCondition.appendTo(group);
        }

        newCondition.appendTo(group);
        parentCondition.find('.btn-or').remove();
        if (group.children('.condition').length > 1) {
            group
                .children('.condition')
                .find('> .condition-container > div')
                .last()
                .append(
                    `<button type="button" class="btn size-s outline btn-or">${translate(
                        'def.or',
                    )}</button>`,
                );
        }

        if (newCondition.prev().length === 0) {
            var field = newCondition.find('.field').detach();
            newCondition.find('.field-container').before(field);
            newCondition.find('.field-container').remove();
        }
    });

    $(document).on('click', '.btn-or', function () {
        var parentCondition = $(this).closest('.condition');
        var newCondition = $(`
          <div class="condition">
            <div class="condition-or" data-translate="def.or"></div>
            <div class="condition-container">
                <select class="field">
                    <option value="ip">IP Address</option>
                    <option value="cookie">Cookie</option>
                    <option value="referer">Referer</option>
                    <option value="request_method">Request Method</option>
                    <option value="user_agent">User Agent</option>
                    <option value="header">Header</option>
                    <option value="lang">${translate(
                        'admin.redirects.lang',
                    )}</option>
                </select>
                <select class="operator">
                    <option value="equals">${translate(
                        'admin.redirects.equals',
                    )}</option>
                    <option value="not_equals">${translate(
                        'admin.redirects.not_equals',
                    )}</option>
                    <option value="contains">${translate(
                        'admin.redirects.contains',
                    )}</option>
                    <option value="not_contains">${translate(
                        'admin.redirects.not_contains',
                    )}</option>
                </select>
                <div>
                    <div class="input-with-desc">
                        <input type="text" class="value" placeholder="${translate(
                            'def.enter_value',
                        )}" required>
                        <p>${translate('admin.redirects.rules.ip')}</p>
                    </div>
                    <button type="button" class="btn size-s outline btn-and" data-translate="def.and"></button>
                    <button type="button" class="btn size-s outline btn-or" data-translate="def.or"></button>
                </div>
                <button type="button" class="btn size-s outline error btn-remove"><i class="ph ph-x"></i></button>
            </div>
          </div>
        `);
        $(newCondition).appendTo('.conditions');
        $('.btn-or').remove();
        $('.conditions .condition')
            .last()
            .find('> .condition-container > div')
            .last()
            .append(
                `<button type="button" class="btn size-s outline btn-or">${translate(
                    'def.or',
                )}</button>`,
            );
    });

    $(document).on('click', '.btn-remove', function () {
        var condition = $(this).closest('.condition');
        var group = condition.closest('.condition-group');
        condition.remove();
        if (group.children().length === 0) {
            console.log(1);
            group.remove();

            if ($('.conditions').children().length === 1) {
                $('.conditions')
                    .find('.condition> .condition-container > div')
                    .last()
                    .append(
                        `<button type="button" class="btn size-s outline btn-or">${translate(
                            'def.or',
                        )}</button>`,
                    );
            }
        } else if (group.children().length === 1) {
            console.log(2);
            var singleCondition = group.children().first();
            singleCondition.find('.label-and').remove();
            singleCondition
                .removeClass('condition-and')
                .appendTo('.conditions');
            group.remove();
            singleCondition.find('.btn-and, .btn-or').remove();
            singleCondition
                .find('> .condition-container > div')
                .last()
                .append(
                    `<button type="button" class="btn size-s outline btn-and">${translate(
                        'def.and',
                    )}</button><button type="button" class="btn size-s outline btn-or">${translate(
                        'def.or',
                    )}</button>`,
                );

            if ($('.conditions').children().length > 1) {
                singleCondition.prepend(
                    `<div class="condition-or">${translate('def.or')}</div>`,
                );
            }
        } else {
            console.log(3);
            group.find('.btn-or').remove();
            group
                .children('.condition')
                .find('> .condition-container > div')
                .last()
                .append(
                    `<button type="button" class="btn size-s outline btn-or">${translate(
                        'def.or',
                    )}</button>`,
                );
        }
    });
});
