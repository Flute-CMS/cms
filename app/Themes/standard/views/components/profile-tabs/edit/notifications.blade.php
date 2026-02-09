<div class="profile-settings__section mb-4" id="notification-channels-settings">
    <x-card>
        <x-slot:header>
            <h4>{{ __('profile.edit.notifications.channels_title') }}</h4>
            <p class="text-muted mb-0">{{ __('profile.edit.notifications.channels_description') }}</p>
        </x-slot:header>

        @foreach ($availableChannels as $channelKey => $channelInfo)
            <div class="d-flex align-items-center justify-content-between {{ !$loop->last ? 'mb-3 pb-3' : '' }}"
                style="{{ !$loop->last ? 'border-bottom: 1px solid var(--transp-1);' : '' }}">
                <div class="d-flex align-items-center gap-3">
                    <x-icon path="{{ $channelInfo['icon'] }}" style="font-size: var(--h5); color: var(--text-500);line-height: 1.2;" />
                    <div>
                        <p class="mb-0" style="font-weight: 500; line-height: 1.2;">{{ $channelInfo['name'] }}</p>
                        <small style="color: var(--text-600);">{{ $channelInfo['description'] }}</small>
                    </div>
                </div>
                <x-fields.toggle
                    name="channel_{{ $channelKey }}"
                    :checked="$channelSettings[$channelKey] ?? true" />
            </div>
        @endforeach

        <x-slot:footer>
            <div class="profile-edit__card-footer">
                <x-button type="primary" size="small" class="w-auto" withLoading yoyo:post="saveChannels"
                    yoyo:on="click">
                    {{ __('def.save') }}
                </x-button>
            </div>
        </x-slot:footer>
    </x-card>
</div>

@if (!empty($groupedTemplates))
    <div class="profile-settings__section mb-4" id="notification-templates-settings">
        <x-card>
            <x-slot:header>
                <h4>{{ __('profile.edit.notifications.templates_title') }}</h4>
                <p class="text-muted mb-0">{{ __('profile.edit.notifications.templates_description') }}</p>
            </x-slot:header>

            @foreach ($groupedTemplates as $module => $templates)
                <div class="{{ !$loop->last ? 'mb-4 pb-2' : '' }}">
                    <p class="mb-2" style="font-size: var(--small); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-600);">
                        {{ $module === 'core' ? __('profile.edit.notifications.core_module') : ucfirst($module) }}
                    </p>

                    @foreach ($templates as $template)
                        @php
                            $templateChannels = $template->getChannels();
                            if (empty($templateChannels)) {
                                $templateChannels = ['inapp'];
                            }
                        @endphp
                        <div class="d-flex align-items-center justify-content-between gap-3 {{ !$loop->last ? 'mb-2 pb-2' : '' }}"
                            style="{{ !$loop->last ? 'border-bottom: 1px solid var(--transp-05);' : '' }}">
                            <p class="mb-0" style="font-size: var(--p); color: var(--text-300);">
                                {{ __($template->title) }}
                            </p>
                            <div class="d-flex align-items-center gap-3" style="flex-shrink: 0;">
                                @foreach ($templateChannels as $channel)
                                    @if (isset($availableChannels[$channel]))
                                        @php
                                            $isEnabled = $templateSettings[$template->key][$channel] ?? ($channelSettings[$channel] ?? true);
                                            $paramName = 'tpl_' . str_replace('.', '__', $template->key) . '_' . $channel;
                                        @endphp
                                        <x-fields.checkbox
                                            name="{{ $paramName }}"
                                            :checked="$isEnabled"
                                            label="{{ $availableChannels[$channel]['name'] }}" />
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach

            <x-slot:footer>
                <div class="profile-edit__card-footer">
                    <x-button type="primary" size="small" class="w-auto" withLoading yoyo:post="saveTemplates"
                        yoyo:on="click">
                        {{ __('def.save') }}
                    </x-button>
                </div>
            </x-slot:footer>
        </x-card>
    </div>
@endif
