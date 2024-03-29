<form>
    @csrf

    <!-- SMTP Включен -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label">
            <label for="smtpEnabled">@t('admin.form_mail.smtp_enabled_label')</label>
            <small class="form-text text-muted">@t('admin.form_mail.smtp_enabled_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="smtpEnabled" role="switch" id="smtpEnabled" type="checkbox" class="form-check-input"
                {{ config('mail.smtp') ? 'checked' : '' }}>
            <label for="smtpEnabled"></label>
        </div>
    </div>

    <!-- SMTP Хост -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label required">
            <label for="smtpHost">@t('admin.form_mail.smtp_host_label')</label>
            <small class="form-text text-muted">@t('admin.form_mail.smtp_host_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="smtpHost" id="smtpHost" placeholder="smtp.example.com" type="text" class="form-control"
                value="{{ config('mail.host') }}" required>
        </div>
    </div>

    <!-- SMTP Порт -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label required">
            <label for="smtpPort">@t('admin.form_mail.smtp_port_label')</label>
            <small class="form-text text-muted">@t('admin.form_mail.smtp_port_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="smtpPort" id="smtpPort" type="number" class="form-control" value="{{ config('mail.port') }}"
                required>
        </div>
    </div>

    <!-- SMTP Отправитель -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label required">
            <label for="smtpFrom">@t('admin.form_mail.smtp_from_label')</label>
            <small class="form-text text-muted">@t('admin.form_mail.smtp_from_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="smtpFrom" id="smtpFrom" placeholder="admin@example.com" type="email" class="form-control"
                value="{{ config('mail.from') }}" required>
        </div>
    </div>

    <!-- SMTP Имя пользователя -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label">
            <label for="smtpUsername">@t('admin.form_mail.smtp_username_label')</label>
            <small class="form-text text-muted">@t('admin.form_mail.smtp_username_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="smtpUsername" id="smtpUsername" type="text" class="form-control"
                value="{{ config('mail.username') }}">
        </div>
    </div>

    <!-- SMTP Пароль -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label">
            <label for="smtpPassword">@t('admin.form_mail.smtp_password_label')</label>
            <small class="form-text text-muted">@t('admin.form_mail.smtp_password_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="smtpPassword" id="smtpPassword" type="password" class="form-control"
                value="{{ config('mail.password') }}">
        </div>
    </div>

    <!-- SMTP Защита -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label">
            <label for="smtpSecure">@t('admin.form_mail.smtp_security_label')</label>
            <small class="form-text text-muted">@t('admin.form_mail.smtp_security_description')</small>
        </div>
        <div class="col-sm-9">
            <select name="smtpSecure" id="smtpSecure" class="form-control">
                <option value="tls" {{ config('mail.secure') == 'tls' ? 'selected' : '' }}>TLS</option>
                <option value="ssl" {{ config('mail.secure') == 'ssl' ? 'selected' : '' }}>SSL</option>
            </select>
        </div>
    </div>

    <div class="position-relative row form-check">
        <div class="col-sm-9 offset-sm-3">
            <button type="submit" data-save class="btn size-m btn--with-icon primary">
                @t('def.save')
                <span class="btn__icon arrow"><i class="ph ph-arrow-right"></i></span>
            </button>
        </div>
    </div>
</form>
