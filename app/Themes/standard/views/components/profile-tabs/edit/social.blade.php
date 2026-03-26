@if (sizeof($socials) > 0)
    <div class="social-cards">
        @foreach ($socials as $network)
            @php
                $userNetwork = $user->hasSocialNetwork($network->key)
                    ? $user->getSocialNetwork($network->key)
                    : null;
                $isLinked = $userNetwork !== null;

                $socialUrl = null;
                $photoUrl = null;

                if ($isLinked) {
                    $socialUrl = $userNetwork->url;
                    if ($network->key === 'Discord' && !empty($userNetwork->value)) {
                        $socialUrl = 'https://discord.com/users/' . $userNetwork->value;
                    }

                    $additional = $userNetwork->getAdditional();
                    if (!empty($additional['photoUrl'])) {
                        $photoUrl = $additional['photoUrl'];
                    }
                }
            @endphp

            <div class="social-card {{ $isLinked ? 'social-card--linked' : '' }}">
                <div class="social-card__header">
                    <div class="social-card__identity">
                        <div class="social-card__avatar">
                            @if ($photoUrl)
                                <img src="{{ $photoUrl }}" alt="{{ $userNetwork->name }}" loading="lazy"
                                    onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" />
                                <span class="social-card__avatar-fallback" style="display:none;">
                                    <x-icon path="{{ $network->icon }}" />
                                </span>
                            @else
                                <span class="social-card__avatar-fallback">
                                    <x-icon path="{{ $network->icon }}" />
                                </span>
                            @endif
                        </div>

                        <div class="social-card__info">
                            <div class="social-card__name-row">
                                <span class="social-card__name">{{ __($network->key) }}</span>
                                <span class="social-card__badge {{ $isLinked ? 'social-card__badge--linked' : '' }}">
                                    {{ __($isLinked ? 'profile.edit.social.linked' : 'profile.edit.social.not_linked') }}
                                </span>
                            </div>

                            @if ($isLinked)
                                <div class="social-card__details">
                                    @if ($userNetwork->name)
                                        <span class="social-card__display-name">{{ $userNetwork->name }}</span>
                                    @endif
                                    @if ($userNetwork->linkedAt)
                                        <span class="social-card__linked-at">
                                            {{ __('profile.edit.social.linked_at', ['date' => $userNetwork->linkedAt->format('d.m.Y')]) }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    @if ($isLinked && $socialUrl)
                        <a href="{{ $socialUrl }}" target="_blank" rel="noopener noreferrer"
                            class="social-card__external" data-tooltip="{{ __('profile.visit_social', ['network' => __($network->key)]) }}">
                            <x-icon path="ph.regular.arrow-square-out" />
                        </a>
                    @endif
                </div>

                <div class="social-card__footer">
                    @if ($isLinked)
                        <div class="social-card__visibility">
                            <x-fields.toggle name="change-visibility-{{ $network->key }}" yoyo:post="changeVisibility"
                                yoyo:val.social-key="{{ $network->key }}" yoyo:on="change delay:200ms"
                                checked="{{ !$userNetwork->hidden }}"
                                label="{{ __($userNetwork->hidden ? 'profile.edit.social.hidden' : 'profile.edit.social.visible') }}" />
                        </div>

                        <button class="social-card__unlink" yoyo:post="removeSocial"
                            hx-flute-confirm="{{ __('profile.edit.social.unlink_description') }}"
                            hx-flute-confirm-type="error" hx-trigger="confirmed"
                            yoyo:val.social-key="{{ $network->key }}">
                            <x-icon path="ph.regular.link-break" />
                            {{ __('profile.edit.social.unlink') }}
                        </button>
                    @else
                        <x-button type="outline-primary" size="small"
                            data-connect="{{ url('profile/social/bind/' . $network->key) }}?popup=1">
                            <x-icon path="ph.regular.plug" />
                            {{ __('profile.edit.social.connect') }}
                        </x-button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@else
    <h5 class="text-muted mt-4">{{ __('profile.edit.social.no_socials') }}</h5>
@endif
