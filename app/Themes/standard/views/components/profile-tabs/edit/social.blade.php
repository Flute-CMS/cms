@if (sizeof($socials) > 0)
    <x-card withoutPadding>
        <div class="page-edit__socials">
            @foreach ($socials as $network)
                @php
                    $userNetwork = $user->hasSocialNetwork($network->key)
                        ? $user->getSocialNetwork($network->key)
                        : null;
                @endphp

                <div class="profile-edit__socials-item">
                    <div class="profile-edit__socials-item-info">
                        <x-icon path="{{ $network->icon }}" />

                        @if ($userNetwork)
                            <div class="profile-edit__socials-main">
                                <div class="d-f flex-center flex-row gap-2">
                                    <h6>{{ __($network->key) }}</h6>
                                    <button class="profile-edit__socials-unbind" yoyo:post="removeSocial"
                                        hx-flute-confirm="{{ __('profile.edit.social.unlink_description') }}"
                                        hx-flute-confirm-type="error" hx-trigger="confirmed"
                                        yoyo:val.social-key="{{ $network->key }}">{{ __('profile.edit.social.unlink') }}</button>
                                </div>
                                <x-link
                                    href="{{ $userNetwork->url }}">{{ $userNetwork->name ?? __('profile.edit.social.default_link') }}</x-link>
                            </div>
                        @else
                            <h6>{{ __($network->key) }}</h6>
                        @endif
                    </div>
                    @if ($userNetwork)
                        <div
                            data-tooltip="{{ __($userNetwork->hidden ? 'profile.edit.social.show_description' : 'profile.edit.social.hide_description') }}">
                            <x-fields.toggle name="change-visibility-{{ $network->key }}" yoyo:post="changeVisibility"
                                yoyo:val.social-key="{{ $network->key }}" yoyo:on="change delay:200ms"
                                checked="{{ !$userNetwork->hidden }}" />
                        </div>
                    @else
                        <x-button type="outline-primary" size="tiny"
                            data-connect="{{ url('profile/social/bind/' . $network->key) }}?popup=1">
                            {{ __('profile.edit.social.connect') }}

                            <x-icon path="ph.regular.plus" />
                        </x-button>
                    @endif
                </div>
            @endforeach
        </div>
    </x-card>
@else
    <h5 class="text-muted mt-4">{{ __('profile.edit.social.no_socials') }}</h5>
@endif
