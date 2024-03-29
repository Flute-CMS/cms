@push('header')
    @at(tt('assets/styles/pages/profile_edit/social.scss'))
@endpush

@push('profile_edit_content')
    <div class="card">
        <div class="card-header">
            @t('profile.s_social.info')
        </div>
        <div class="profile_settings">
            <div class="profile_settings_setting">
                <div class="profile_settings_setting_title">@t('profile.s_social.connected')</div>
                <div class="profile_settings_setting_container">
                    @if (sizeof($user->socialNetworks) > 0)
                        <div class="socials_container">
                            @foreach ($user->socialNetworks as $network)
                                <div class="profile_setting_social">
                                    <div class="social_name_icon">
                                        {!! $network->socialNetwork->icon !!}
                                        <div>{{ $network->socialNetwork->key }}</div>
                                    </div>
                                    <div>{{ $network->name }}</div>

                                    @if ((bool) $network->hidden === true)
                                        <i data-show="{{ $network->socialNetwork->key }}" class="ph ph-eye-slash"></i>
                                    @else
                                        <i data-hide="{{ $network->socialNetwork->key }}" class="ph ph-eye"></i>
                                    @endif

                                    <a href="{{ url('profile/social/unbind/' . $network->socialNetwork->key) }}">@t('profile.s_social.disconnect')</a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            <div class="profile_settings_setting">
                <div class="profile_settings_setting_title">@t('profile.s_social.not_connected')</div>
                <div class="profile_settings_setting_container">
                    @if (sizeof($socials) > 0)
                        <div class="socials_container connection">
                            @foreach ($socials as $network)
                                @if (!user()->hasSocialNetwork($network->key))
                                    <div class="profile_setting_social">
                                        <div class="social_name_icon">
                                            {!! $network->icon !!}
                                            <div>{{ $network->key }}</div>
                                        </div>
                                        <a data-connect="{{ url('profile/social/bind/' . $network->key) }}">@t('profile.s_social.connect')</a>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endpush

@push('footer')
    @at(tt('assets/js/pages/profile_edit/social.js'))
@endpush
