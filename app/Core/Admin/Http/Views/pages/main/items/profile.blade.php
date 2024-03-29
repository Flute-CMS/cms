<form>
    @csrf

    <!-- Максимальный размер баннера -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label required">
            <label for="maxBannerSize">@t('admin.form_profile.max_banner_size_label')</label>
            <small class="form-text text-muted">@t('admin.form_profile.max_banner_size_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="maxBannerSize" id="maxBannerSize" type="number" class="form-control"
                value="{{ config('profile.max_banner_size') }}" required>
        </div>
    </div>

    <!-- Максимальный размер аватара -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label required">
            <label for="maxAvatarSize">@t('admin.form_profile.max_avatar_size_label')</label>
            <small class="form-text text-muted">@t('admin.form_profile.max_avatar_size_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="maxAvatarSize" id="maxAvatarSize" type="number" class="form-control"
                value="{{ config('profile.max_avatar_size') }}" required>
        </div>
    </div>

    <!-- Типы файлов баннера -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label required">
            <label>@t('admin.form_profile.banner_file_types_label')</label>
            <small class="form-text text-muted">@t('admin.form_profile.banner_file_types_description')</small>
        </div>
        <div class="col-sm-9">
            <div class="profile_checkboxes">
                <div class="form-checkbox">
                    <input class="form-check-input" name="banner_types[image/png]" type="checkbox" value="image/png"
                        id="banner_types[image/png]" @if (in_array('image/png', config('profile.banner_types'))) checked @endif>
                    <label class="form-check-label" for="banner_types[image/png]">
                        image/png
                    </label>
                </div>
                <div class="form-checkbox">
                    <input class="form-check-input" name="banner_types[image/jpeg]" type="checkbox" value="image/jpeg"
                        id="banner_types[image/jpeg]" @if (in_array('image/jpeg', config('profile.banner_types'))) checked @endif>
                    <label class="form-check-label" for="banner_types[image/jpeg]">
                        image/jpeg
                    </label>
                </div>
                <div class="form-checkbox">
                    <input class="form-check-input" name="banner_types[image/gif]" type="checkbox" value="image/gif"
                        id="banner_types[image/gif]" @if (in_array('image/gif', config('profile.banner_types'))) checked @endif>
                    <label class="form-check-label" for="banner_types[image/gif]">
                        image/gif
                    </label>
                </div>
                <div class="form-checkbox">
                    <input class="form-check-input" name="banner_types[image/webp]" type="checkbox" value="image/webp"
                        id="banner_types[image/webp]" @if (in_array('image/webp', config('profile.banner_types'))) checked @endif>
                    <label class="form-check-label" for="banner_types[image/webp]">
                        image/webp
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Типы файлов аватара -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label required">
            <label>@t('admin.form_profile.avatar_file_types_label')</label>
            <small class="form-text text-muted">@t('admin.form_profile.avatar_file_types_description')</small>
        </div>
        <div class="col-sm-9">
            <div class="profile_checkboxes">
                <div class="form-checkbox">
                    <input class="form-check-input" name="avatar_types[image/png]" type="checkbox" value="image/png"
                        id="avatarType[image/png]" @if (in_array('image/png', config('profile.avatar_types'))) checked @endif>
                    <label class="form-check-label" for="avatarType[image/png]">
                        image/png
                    </label>
                </div>
                <div class="form-checkbox">
                    <input class="form-check-input" name="avatar_types[image/jpeg]" type="checkbox" value="image/jpeg"
                        id="avatarType[image/jpeg]" @if (in_array('image/jpeg', config('profile.avatar_types'))) checked @endif>
                    <label class="form-check-label" for="avatarType[image/jpeg]">
                        image/jpeg
                    </label>
                </div>
                <div class="form-checkbox">
                    <input class="form-check-input" name="avatar_types[image/gif]" type="checkbox" value="image/gif"
                        id="avatarType[image/gif]" @if (in_array('image/gif', config('profile.avatar_types'))) checked @endif>
                    <label class="form-check-label" for="avatarType[image/gif]">
                        image/gif
                    </label>
                </div>
                <div class="form-checkbox">
                    <input class="form-check-input" name="avatar_types[image/webp]" type="checkbox" value="image/webp"
                        id="avatarType[image/webp]" @if (in_array('image/webp', config('profile.avatar_types'))) checked @endif>
                    <label class="form-check-label" for="avatarType[image/webp]">
                        image/webp
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Изменение URI -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label">
            <label for="changeUri">@t('admin.form_profile.change_uri_label')</label>
            <small class="form-text text-muted">@t('admin.form_profile.change_uri_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="changeUri" role="switch" id="changeUri" type="checkbox" class="form-check-input"
                {{ config('profile.change_uri') ? 'checked' : '' }}>
            <label for="changeUri"></label>
        </div>
    </div>

    <!-- Конвертация в WebP -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label">
            <label for="convertToWebp">@t('admin.form_profile.convert_to_webp_label')</label>
            <small class="form-text text-muted">@t('admin.form_profile.convert_to_webp_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="convertToWebp" role="switch" id="convertToWebp" type="checkbox" class="form-check-input"
                {{ config('profile.convert_to_webp') ? 'checked' : '' }}>
            <label for="convertToWebp"></label>
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
