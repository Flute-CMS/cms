@push('header')
    @at(tt('assets/styles/pages/profile_edit/edit_main.scss'))
@endpush

@push('profile_edit_content')
    <div class="card">
        <div class="card-header">
            @t('profile.s_main.info')
        </div>
        <div class="profile_settings">
            <div class="profile_settings_setting">
                <div class="profile_settings_setting_title">@t('profile.s_main.privacy')</div>
                <div class="profile_settings_setting_container">
                    <div class="privacy_container">
                        <div class="privacy_container_buttons">
                            <button @if (!$user->hidden) class="active" @endif
                                id="on">@t('profile.s_main.profile_show')</button>
                            <button @if ($user->hidden) class="active" @endif
                                id="off">@t('profile.s_main.profile_hidden')</button>
                        </div>
                        <div class="background" @if ($user->hidden) style="left: 50%" @endif></div>
                    </div>
                </div>
            </div>
            <div class="profile_settings_setting">
                <div class="row">
                    <div class="col-md-6">
                        <div class="profile_settings_setting_title">@t('profile.s_main.avatar')</div>
                        <div class="profile_settings_setting_container">
                            <div class="profile_setting_image">
                                <div class="profile_setting_img setting_avatar">
                                    @if ($user->avatar !== config('profile.default_avatar'))
                                        <i class="icon_empty ph ph-image"></i>
                                        <img src="{{ url($user->avatar) }}" loading="lazy" alt="">
                                        <div class="overlay" data-delete="avatar">
                                            <i class="icon ph ph-trash"></i>
                                        </div>
                                    @else
                                        <img style="display: none" alt="">
                                        <i class="icon_empty ph ph-image" style="display: block"></i>
                                        <div class="overlay" data-delete="avatar" style="display: none;">
                                            <i class="icon ph ph-trash"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="profile_image_flex">
                                    <div>
                                        @t('profile.s_main.max_size', [
                                            '%' => config('profile.max_avatar_size'),
                                        ])
                                    </div>
                                    <label class="upload_image">
                                        <input type="file" id="upload_avatar" data-type="avatar"
                                            accept="{{ implode(', ', config('profile.avatar_types')) }}" />
                                        @t('profile.s_main.load_avatar')
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile_settings_setting_title">@t('profile.s_main.banner')</div>
                        <div class="profile_setting_image">
                            <div class="profile_setting_img setting_banner">
                                @if ($user->banner !== config('profile.default_banner'))
                                    <i class="icon_empty ph ph-image"></i>
                                    <img src="{{ url($user->banner) }}" loading="lazy" alt="">
                                    <div class="overlay" data-delete="banner">
                                        <i class="icon ph ph-trash"></i>
                                    </div>
                                @else
                                    <img style="display: none" alt="">
                                    <i class="icon_empty ph ph-image" style="display: block"></i>
                                    <div class="overlay" data-delete="banner" style="display: none;">
                                        <i class="icon ph ph-trash"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="profile_image_flex">
                                <div>@t('profile.s_main.max_size', [
                                    '%' => config('profile.max_banner_size'),
                                ])</div>
                                <label class="upload_image">
                                    <input type="file" id="upload_banner" data-type="banner"
                                        accept="{{ implode(', ', config('profile.banner_types')) }}" />
                                    @t('profile.s_main.load_banner')
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('footer')
    @at(tt('assets/js/pages/profile_edit/main.js'))
@endpush
