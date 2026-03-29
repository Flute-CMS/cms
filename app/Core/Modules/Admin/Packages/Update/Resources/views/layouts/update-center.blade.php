@php
    $hasCurrentUpdates = !empty($update) || !empty($modules) || !empty($themes);
    $hasOtherUpdates = !empty($other_update) || !empty($other_modules) || !empty($other_themes);
    $totalCurrent = ($update ? 1 : 0) + count($modules) + count($themes);
    $channelLabel = [
        'stable' => __('admin-update.channel_stable'),
        'early' => __('admin-update.channel_early'),
    ];
@endphp

<div class="su">
    <div class="su-bar">
        <div class="su-bar-left">
            <span class="su-bar-ver">Flute v{{ $current_version }}</span>
            <span class="su-pill su-pill--{{ $active_channel }}">
                <span class="su-pill-dot"></span>
                {{ $channelLabel[$active_channel] ?? $active_channel }}
            </span>
        </div>
        <div class="su-bar-right">
            <div class="su-channel-seg" role="radiogroup">
                <button class="su-chseg {{ $active_channel === 'stable' ? 'is-on' : '' }}"
                    role="radio" aria-checked="{{ $active_channel === 'stable' ? 'true' : 'false' }}"
                    yoyo:val.channel="stable" yoyo:post="switchChannel">
                    {{ __('admin-update.channel_stable') }}
                </button>
                <button class="su-chseg {{ $active_channel === 'early' ? 'is-on' : '' }}"
                    role="radio" aria-checked="{{ $active_channel === 'early' ? 'true' : 'false' }}"
                    yoyo:val.channel="early" yoyo:post="switchChannel">
                    {{ __('admin-update.channel_early') }}
                </button>
            </div>
            <x-button size="sm" type="outline" yoyo:post="handleCheckUpdates">
                <x-icon path="ph.bold.arrows-clockwise-bold" />
                {{ __('admin-update.check_updates') }}
            </x-button>
            @if ($hasCurrentUpdates)
                <x-button size="sm" type="accent" yoyo:post="handleUpdateAll"
                    hx-flute-confirm="{{ __('admin-update.update_all_confirm') }}" hx-flute-confirm-type="success">
                    <x-icon path="ph.bold.arrow-circle-up-bold" />
                    {{ __('admin-update.update_all') }}
                </x-button>
            @endif
        </div>
    </div>

    @if ($hasCurrentUpdates)
        @if (!empty($update))
            <div class="su-group">
                @include('admin-update::layouts.partials.update-card', [
                    'item' => $update,
                    'type' => 'cms',
                    'itemId' => null,
                    'name' => 'Flute CMS',
                    'current_ver' => $current_version,
                    'is_primary' => true,
                ])
            </div>
        @endif

        @if (!empty($modules))
            <div class="su-section-label">{{ __('admin-update.update_modules') }}</div>
            <div class="su-group">
                @foreach ($modules as $moduleId => $m)
                    @include('admin-update::layouts.partials.update-card', [
                        'item' => $m,
                        'type' => 'module',
                        'itemId' => $moduleId,
                        'name' => $m['name'],
                        'current_ver' => $m['current_version'] ?? '?',
                    ])
                @endforeach
            </div>
        @endif

        @if (!empty($themes))
            <div class="su-section-label">{{ __('admin-update.update_themes') }}</div>
            <div class="su-group">
                @foreach ($themes as $themeId => $t)
                    @include('admin-update::layouts.partials.update-card', [
                        'item' => $t,
                        'type' => 'theme',
                        'itemId' => $themeId,
                        'name' => $t['name'],
                        'current_ver' => $t['current_version'] ?? '?',
                    ])
                @endforeach
            </div>
        @endif
    @else
        @include('admin-update::components.no-updates')
    @endif

    @if ($hasOtherUpdates)
        <div class="su-divider"></div>

        <div class="su-other-head">
            <span class="su-other-title">
                {{ __('admin-update.on_other_channel', ['channel' => $channelLabel[$other_channel] ?? $other_channel]) }}
            </span>
            <button class="su-other-switch" yoyo:val.channel="{{ $other_channel }}" yoyo:post="switchChannel">
                {{ __('admin-update.switch_to_channel', ['channel' => $channelLabel[$other_channel] ?? $other_channel]) }}
                <x-icon path="ph.bold.arrow-right-bold" />
            </button>
        </div>

        @if (!empty($other_update))
            <div class="su-group su-group--muted">
                @include('admin-update::layouts.partials.update-card', [
                    'item' => $other_update,
                    'type' => 'cms',
                    'itemId' => null,
                    'name' => 'Flute CMS',
                    'current_ver' => $current_version,
                    'is_primary' => true,
                    'catalog' => true,
                ])
            </div>
        @endif

        @if (!empty($other_modules))
            <div class="su-section-label">{{ __('admin-update.update_modules') }}</div>
            <div class="su-group su-group--muted">
                @foreach ($other_modules as $moduleId => $m)
                    @include('admin-update::layouts.partials.update-card', [
                        'item' => $m,
                        'type' => 'module',
                        'itemId' => $moduleId,
                        'name' => $m['name'],
                        'current_ver' => $m['current_version'] ?? '?',
                        'catalog' => true,
                    ])
                @endforeach
            </div>
        @endif

        @if (!empty($other_themes))
            <div class="su-section-label">{{ __('admin-update.update_themes') }}</div>
            <div class="su-group su-group--muted">
                @foreach ($other_themes as $themeId => $t)
                    @include('admin-update::layouts.partials.update-card', [
                        'item' => $t,
                        'type' => 'theme',
                        'itemId' => $themeId,
                        'name' => $t['name'],
                        'current_ver' => $t['current_version'] ?? '?',
                        'catalog' => true,
                    ])
                @endforeach
            </div>
        @endif
    @endif
</div>
