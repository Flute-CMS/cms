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

    <!-- footer_name -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label">
            <label for="footer_name">
                @t('admin.app.footer_name_label')
            </label>
            <small class="form-text text-muted">@t('admin.app.footer_name_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="footer_name" id="footer_name" placeholder="@t('admin.app.footer_name_label')" type="text"
                class="form-control" value="{{ config('app.footer_name', config('app.name')) }}">
        </div>
    </div>

    {{-- <div class="position-relative row form-group align-items-start">
        <div class="col-sm-3 col-form-label">
            <label for="footer_html">
                @t('admin.app.footer_html_label')
            </label>
            <small class="form-text text-muted">@t('admin.app.footer_html_description')</small>
        </div>
        <div class="col-sm-9">
            <div class="editor-ace" id="editor" data-editor-lang="html">{!! config('app.footer_html') !!}</div>
        </div>
    </div> --}}

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

    <!-- Часовой пояс -->
    <div class="position-relative row form-group withoutLine">
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

    <div class="form-group-lines">@t('admin.app.debug_mode_label')</div>

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
    <div class="position-relative row form-group withoutLine">
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

    <div class="form-group-lines">@t('admin.app.maintenance_mode')</div>

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

    <div class="position-relative row form-group withoutLine">
        <div class="col-sm-3 col-form-label">
            <label for="maintenance_message">
                @t('admin.app.maintenance_message')
            </label>
            <small class="form-text text-muted">@t('admin.app.maintenance_message_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="maintenance_message" id="maintenance_message" placeholder="@t('admin.app.maintenance_message')"
                type="text" class="form-control" value="{{ config('app.maintenance_message') }}">
        </div>
    </div>

    <div class="form-group-lines">@t('def.pictures')</div>

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

    <div class="position-relative row form-group align-items-start withoutLine">
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

@push('footer')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/js-beautify/1.15.1/beautify.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.12/ace.js" type="text/javascript" charset="utf-8"></script>
@endpush
