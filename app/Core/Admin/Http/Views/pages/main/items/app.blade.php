<form>
    @csrf

    <!-- Имя -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label required">
            <label for="name">
                @t('admin.app.name_label')
            </label>
            <small class="form-text text-muted">@t('admin.app.name_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="name" id="name" placeholder="@t('admin.app.name_label')" type="text" class="form-control"
                value="{{ config('app.name') }}" required>
        </div>
    </div>

    <!-- URL -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label required">
            <label for="url">
                @t('admin.app.url_label')
            </label>
            <small class="form-text text-muted">@t('admin.app.url_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="url" id="url" placeholder="@t('admin.app.url_label')" type="url" class="form-control"
                value="{{ config('app.url') }}" required>
        </div>
    </div>

    <!-- STEAMAPI -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label">
            <label for="url">
                @t('admin.app.steam_label')
            </label>
            <small class="form-text text-muted">@t('admin.app.steam_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="steam_api" id="steam_api" placeholder="@t('admin.app.steam_label')" type="password" class="form-control"
                value="{{ config('app.steam_api') }}">
        </div>
    </div>

    <!-- Режим отладки -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label">
            <label for="debug">
                @t('admin.app.debug_mode_label')</label>
            <small class="form-text text-muted">@t('admin.app.debug_mode_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="debug" role="switch" id="debug" type="checkbox" class="form-check-input"
                {{ config('app.debug') ? 'checked' : '' }}>
            <label for="debug"></label>
        </div>
    </div>

    <!-- Debug IP's -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label">
            <label for="debugIps">
                @t('admin.app.debug_ips_label')
            </label>
            <small class="form-text text-muted">@t('admin.app.debug_ips_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="debugIps" id="debugIps" placeholder="@t('admin.app.debug_ips_label')" type="text" class="form-control"
                value="{{ implode(', ', config('app.debug_ips')) }}">

            <a id="getMyIp" class="mt-3">@t('admin.app.get_ip')</a>
            <small id="myIp" class="form-text text-muted"></small>
        </div>
    </div>

    <!-- Режим производительности -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label">
            <label for="performanceMode">
                <div class="may_unstable" data-tooltip="@t('admin.may_have_errors')" data-tooltip-conf="right multiline"
                    data-faq="@t('admin.what_it_means')" data-faq-content="@t('admin.app.performance_warning')">
                    <i class="ph ph-warning"></i>
                </div>
                @t('admin.app.performance_mode_label')
            </label>
            <small class="form-text text-muted">@t('admin.app.performance_mode_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="performanceMode" role="switch" id="performanceMode" type="checkbox" class="form-check-input"
                {{ config('app.mode') == 'performance' ? 'checked' : '' }}>
            <label for="performanceMode"></label>
        </div>
    </div>

    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label">
            <label for="maintenance_mode">
                <div class="may_unstable" data-tooltip="@t('admin.app.will_close_site')" data-tooltip-conf="right multiline"
                    data-faq="@t('admin.what_it_means')" data-faq-content="@t('admin.app.maintenance_warning')">
                    <i class="ph ph-warning"></i>
                </div>
                @t('admin.app.maintenance_mode')
            </label>
            <small class="form-text text-muted">@t('admin.app.maintenance_mode_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="maintenance_mode" role="switch" id="maintenance_mode" type="checkbox" class="form-check-input"
                {{ config('app.maintenance_mode') == true ? 'checked' : '' }}>
            <label for="maintenance_mode"></label>
        </div>
    </div>

    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label">
            <label for="discord_link_roles">
                @t('admin.app.discord_link_roles')
            </label>
            <small class="form-text text-muted">@t('admin.app.discord_link_roles_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="discord_link_roles" role="switch" id="discord_link_roles" type="checkbox" class="form-check-input"
                {{ config('app.discord_link_roles') == true ? 'checked' : '' }}>
            <label for="discord_link_roles"></label>
        </div>
    </div>

    <!-- Подсказки -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label">
            <label>
                @t('admin.app.hints_label')
            </label>
            <small class="form-text text-muted">@t('admin.app.hints_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="tips" role="switch" id="tips" type="checkbox" class="form-check-input"
                {{ config('app.tips') ? 'checked' : '' }}>
            <label for="tips"></label>
        </div>
    </div>

    <!-- Поделиться -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label">
            <label for="share">
                @t('admin.app.error_sharing_label')</label>
            <small class="form-text text-muted">@t('admin.app.error_sharing_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="share" role="switch" id="share" type="checkbox" class="form-check-input"
                {{ config('app.share') ? 'checked' : '' }}>
            <label for="share"></label>
        </div>
    </div>

    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label">
            <label for="flute_copyright">
                @t('admin.app.flute_copyright_label')</label>
            <small class="form-text text-muted">@t('admin.app.flute_copyright_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="flute_copyright" role="switch" id="flute_copyright" type="checkbox"
                class="form-check-input" {{ config('app.flute_copyright') ? 'checked' : '' }}>
            <label for="flute_copyright"></label>
        </div>
    </div>

    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label">
            <label for="widget_placeholders">
                @t('admin.app.widget_placeholders_label')</label>
            <small class="form-text text-muted">@t('admin.app.widget_placeholders_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="widget_placeholders" role="switch" id="widget_placeholders" type="checkbox"
                class="form-check-input" {{ config('app.widget_placeholders') ? 'checked' : '' }}>
            <label for="widget_placeholders"></label>
        </div>
    </div>

    <!-- Часовой пояс -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label required">
            <label for="timezone">
                @t('admin.app.timezone_label')
            </label>
            <small class="form-text text-muted">@t('admin.app.timezone_description')</small>
        </div>
        <div class="col-sm-9">
            <input required name="timezone" id="timezone" placeholder="@t('admin.app.timezone_label')" type="text"
                class="form-control" value="{{ config('app.timezone') }}">
        </div>
    </div>

    <!-- Уведомления -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label required">
            <label for="notifications">
                @t('admin.app.notifications_label')
            </label>
            <small class="form-text text-muted">@t('admin.app.notifications_description')</small>
        </div>
        <div class="col-sm-9">
            <select name="notifications" id="notifications" class="form-control">
                <option value="all" {{ config('app.notifications') == 'all' ? 'selected' : '' }}>
                    @t('admin.app.notifications_all')
                </option>
                <option value="unread" {{ config('app.notifications') == 'unread' ? 'selected' : '' }}>
                    @t('admin.app.notifications_unread')
                </option>
            </select>
        </div>
    </div>

    <div class="position-relative row form-group align-items-start">
        <div class="col-sm-3 col-form-label required">
            <label for="favicon">
                Favicon
            </label>
            <small>@t('admin.app.favicon_desc')</small>
        </div>
        <div class="col-sm-9">
            <div class="d-flex flex-column">
                <input type="file" name="favicon" id="favicon" class="form-control" accept="image/*">
                <img class="example-image" src="@asset('favicon.ico')" alt="">
            </div>
        </div>
    </div>

    <div class="position-relative row form-group align-items-start">
        <div class="col-sm-3 col-form-label required">
            <label for="logo">
                @t('admin.app.logo')
            </label>
        </div>
        <div class="col-sm-9">
            <div class="d-flex flex-column">
                <input type="file" name="logo" id="logo" class="form-control" accept="image/*">
                <img class="example-image" src="@asset(config('app.logo'))" alt="">
            </div>
        </div>
    </div>

    <div class="position-relative row form-group align-items-start">
        <div class="col-sm-3 col-form-label required">
            <label for="bg_image">
                @t('admin.app.bg_image')
            </label>
        </div>
        <div class="col-sm-9">
            <div class="d-flex flex-column">
                <input type="file" name="bg_image" id="bg_image" class="form-control" accept="image/*">
                <div class="remove-bg" data-tooltip="@t('def.delete')">
                    <img class="example-image bg-image" src="@asset(config('app.bg_image'))" alt="">
                </div>
            </div>
        </div>
    </div>

    <!-- Кнопка отправки -->
    <div class="position-relative row form-check">
        <div class="col-sm-9 offset-sm-3">
            <button type="submit" data-save class="btn size-m btn--with-icon primary">
                @t('def.save')
                <span class="btn__icon arrow"><i class="ph ph-arrow-right"></i></span>
            </button>
        </div>
    </div>
</form>
