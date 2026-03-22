@if(config('app.notifications_sound_enabled', true))
<div class="notif-settings__section" id="notification-sound-settings">
    <div class="notif-settings__section-header">
        <div class="notif-settings__section-icon">
            <x-icon path="ph.bold.speaker-high-bold" />
        </div>
        <div>
            <h4 class="notif-settings__section-title">{{ __('profile.edit.notifications.sound_title') }}</h4>
            <p class="notif-settings__section-desc">{{ __('profile.edit.notifications.sound_description') }}</p>
        </div>
    </div>

    <div class="notif-settings__item">
        <div class="notif-settings__item-info">
            <span class="notif-settings__item-name">{{ __('profile.edit.notifications.sound_label') }}</span>
            <span class="notif-settings__item-hint">{{ __('profile.edit.notifications.sound_hint') }}</span>
        </div>
        <label class="toggle-switch">
            <input type="checkbox" id="notification-sound-profile-toggle" class="toggle-switch-input">
            <span class="toggle-switch-slider"></span>
        </label>
    </div>
</div>
@endif

<div class="notif-settings__section" id="notification-channels-settings">
    <div class="notif-settings__section-header">
        <div class="notif-settings__section-icon">
            <x-icon path="ph.bold.broadcast-bold" />
        </div>
        <div>
            <h4 class="notif-settings__section-title">{{ __('profile.edit.notifications.channels_title') }}</h4>
            <p class="notif-settings__section-desc">{{ __('profile.edit.notifications.channels_description') }}</p>
        </div>
    </div>

    <div class="notif-settings__channels">
        @foreach ($availableChannels as $channelKey => $channelInfo)
            <div class="notif-settings__channel">
                <div class="notif-settings__channel-left">
                    <div class="notif-settings__channel-icon">
                        <x-icon path="{{ $channelInfo['icon'] }}" />
                    </div>
                    <div class="notif-settings__channel-info">
                        <span class="notif-settings__channel-name">{{ $channelInfo['name'] }}</span>
                        <span class="notif-settings__channel-desc">{{ $channelInfo['description'] }}</span>
                    </div>
                </div>
                <x-fields.toggle
                    name="channel_{{ $channelKey }}"
                    :checked="$channelSettings[$channelKey] ?? true" />
            </div>
        @endforeach
    </div>

    <div class="notif-settings__footer">
        <x-button type="primary" size="small" class="w-auto" withLoading yoyo:post="saveChannels"
            yoyo:on="click">
            <x-icon path="ph.bold.floppy-disk-bold" />
            {{ __('def.save') }}
        </x-button>
    </div>
</div>

@if (!empty($groupedTemplates))
    <div class="notif-settings__section" id="notification-templates-settings">
        <div class="notif-settings__section-header">
            <div class="notif-settings__section-icon">
                <x-icon path="ph.bold.sliders-bold" />
            </div>
            <div>
                <h4 class="notif-settings__section-title">{{ __('profile.edit.notifications.templates_title') }}</h4>
                <p class="notif-settings__section-desc">{{ __('profile.edit.notifications.templates_description') }}</p>
            </div>
        </div>

        <div class="notif-settings__templates">
            @foreach ($groupedTemplates as $module => $templates)
                <div class="notif-settings__group">
                    <div class="notif-settings__group-label">
                        {{ $module === 'core' ? __('profile.edit.notifications.core_module') : ucfirst($module) }}
                    </div>

                    <div class="notif-settings__group-items">
                        @foreach ($templates as $template)
                            @php
                                $templateChannels = $template->getChannels();
                                if (empty($templateChannels)) {
                                    $templateChannels = ['inapp'];
                                }
                            @endphp
                            @php
                                $keyPart = str_contains($template->key, '.') ? substr($template->key, strpos($template->key, '.') + 1) : $template->key;
                                $i18nKey = ($template->module ?? 'core') === 'core'
                                    ? 'notifications.templates.' . $keyPart
                                    : strtolower($template->module ?? 'core') . '.notifications.template_' . $keyPart;
                                $cleanTitle = __($i18nKey);
                                if ($cleanTitle === $i18nKey) {
                                    $cleanTitle = ucfirst(str_replace('_', ' ', $keyPart));
                                }
                            @endphp
                            <div class="notif-settings__template">
                                @if($template->icon)
                                    <x-icon path="{{ $template->icon }}" class="notif-settings__template-icon" />
                                @endif
                                <span class="notif-settings__template-name">{{ $cleanTitle }}</span>
                                <div class="notif-settings__template-toggles">
                                    @foreach ($templateChannels as $channel)
                                        @if (isset($availableChannels[$channel]))
                                            @php
                                                $isEnabled = $templateSettings[$template->key][$channel] ?? ($channelSettings[$channel] ?? true);
                                                $paramName = 'tpl_' . str_replace('.', '__', $template->key) . '_' . $channel;
                                            @endphp
                                            <label class="notif-settings__template-chip {{ $isEnabled ? 'is-active' : '' }}">
                                                <input type="checkbox" name="{{ $paramName }}" {{ $isEnabled ? 'checked' : '' }}
                                                    onchange="this.closest('.notif-settings__template-chip').classList.toggle('is-active', this.checked)">
                                                <x-icon path="{{ $availableChannels[$channel]['icon'] }}" />
                                                <span>{{ $availableChannels[$channel]['name'] }}</span>
                                            </label>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="notif-settings__footer">
            <x-button type="primary" size="small" class="w-auto" withLoading yoyo:post="saveTemplates"
                yoyo:on="click">
                <x-icon path="ph.bold.floppy-disk-bold" />
                {{ __('def.save') }}
            </x-button>
        </div>
    </div>
@endif
