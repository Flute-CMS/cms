<form>
    @csrf

    <!-- Запомнить меня -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label">
            <label for="rememberMe">
                @t('admin.auth_form.remember_me_label')
            </label>
            <small class="form-text text-muted">@t('admin.auth_form.remember_me_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="rememberMe" role="switch" id="rememberMe" type="checkbox" class="form-check-input"
                {{ config('auth.remember_me') ? 'checked' : '' }}>
            <label for="rememberMe"></label>
        </div>
    </div>

    <!-- Продолжительность запоминания -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label required">
            <label for="rememberMeDuration">
                @t('admin.auth_form.remember_me_duration_label')
            </label>
            <small class="form-text text-muted">@t('admin.auth_form.remember_me_duration_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="rememberMeDuration" id="rememberMeDuration" type="number" class="form-control"
                value="{{ config('auth.remember_me_duration') }}" required>
            <small id="durationReadable" class="form-text text-muted"></small>
        </div>
    </div>

    <!-- CSRF защита -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label">
            <label for="csrfEnabled">
                @t('admin.auth_form.csrf_protection_label')
            </label>
            <small class="form-text text-muted">@t('admin.auth_form.csrf_protection_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="csrfEnabled" role="switch" id="csrfEnabled" type="checkbox" class="form-check-input"
                {{ config('auth.csrf_enabled') ? 'checked' : '' }}>
            <label for="csrfEnabled"></label>
        </div>
    </div>

    <!-- Сброс пароля -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label">
            <label for="resetPassword">
                @t('admin.auth_form.reset_password_label')
            </label>
            <small class="form-text text-muted">@t('admin.auth_form.reset_password_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="resetPassword" role="switch" id="resetPassword" type="checkbox" class="form-check-input"
                {{ config('auth.reset_password') ? 'checked' : '' }}>
            <label for="resetPassword"></label>
        </div>
    </div>

    <!-- Токен безопасности -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label">
            <label for="securityToken">
                @t('admin.auth_form.security_token_label')
            </label>
            <small class="form-text text-muted">@t('admin.auth_form.security_token_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="securityToken" role="switch" id="securityToken" type="checkbox" class="form-check-input"
                {{ config('auth.security_token') ? 'checked' : '' }}>
            <label for="securityToken"></label>
        </div>
    </div>

    <!-- Регистрация -->
    <!-- Подтверждение Email -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label">
            <label for="confirmEmail">@t('admin.auth_form.confirm_email_label')</label>
            <small class="form-text text-muted">@t('admin.auth_form.confirm_email_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="confirmEmail" role="switch" id="confirmEmail" type="checkbox" class="form-check-input"
                {{ config('auth.registration.confirm_email') ? 'checked' : '' }}>
            <label for="confirmEmail"></label>
        </div>
    </div>

    <!-- Поддержка социальных сетей -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label">
            <label for="socialSupplement">@t('admin.auth_form.social_support_label')</label>
            <small class="form-text text-muted">@t('admin.auth_form.social_support_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="socialSupplement" role="switch" id="socialSupplement" type="checkbox" class="form-check-input"
                {{ config('auth.registration.social_supplement') ? 'checked' : '' }}>
            <label for="socialSupplement"></label>
        </div>
    </div>

    <!-- Валидация -->
    <!-- Валидация логина -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label required">
            <label for="loginMinLength">@t('admin.auth_form.login_min_length_label')</label>
            <small class="form-text text-muted">@t('admin.auth_form.login_min_length_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="loginMinLength" id="loginMinLength" type="number" class="form-control"
                value="{{ config('auth.validation.login.min_length') }}" required>
        </div>
    </div>

    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label required">
            <label for="loginMaxLength">@t('admin.auth_form.login_max_length_label')</label>
            <small class="form-text text-muted">@t('admin.auth_form.login_max_length_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="loginMaxLength" id="loginMaxLength" type="number" class="form-control"
                value="{{ config('auth.validation.login.max_length') }}" required>
        </div>
    </div>

    <!-- Валидация пароля -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label required">
            <label for="passwordMinLength">@t('admin.auth_form.password_min_length_label')</label>
            <small class="form-text text-muted">@t('admin.auth_form.password_min_length_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="passwordMinLength" id="passwordMinLength" type="number" class="form-control"
                value="{{ config('auth.validation.password.min_length') }}" required>
        </div>
    </div>

    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label required">
            <label for="passwordMaxLength">@t('admin.auth_form.password_max_length_label')</label>
            <small class="form-text text-muted">@t('admin.auth_form.password_max_length_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="passwordMaxLength" id="passwordMaxLength" type="number" class="form-control"
                value="{{ config('auth.validation.password.max_length') }}" required>
        </div>
    </div>

    <!-- Валидация имени -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label required">
            <label for="nameMinLength">@t('admin.auth_form.name_min_length_label')</label>
            <small class="form-text text-muted">@t('admin.auth_form.name_min_length_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="nameMinLength" id="nameMinLength" type="number" class="form-control"
                value="{{ config('auth.validation.name.min_length') }}" required>
        </div>
    </div>

    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label required">
            <label for="nameMaxLength">@t('admin.auth_form.name_max_length_label')</label>
            <small class="form-text text-muted">@t('admin.auth_form.name_max_length_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="nameMaxLength" id="nameMaxLength" type="number" class="form-control"
                value="{{ config('auth.validation.name.max_length') }}" required>
        </div>
    </div>

    <!-- Кнопка сохранения -->
    <div class="position-relative row form-check">
        <div class="col-sm-9 offset-sm-3">
            <button type="submit" data-save class="btn size-m btn--with-icon primary">
                @t('def.save')
                <span class="btn__icon arrow"><i class="ph ph-arrow-right"></i></span>
            </button>
        </div>
    </div>
</form>
