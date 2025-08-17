<div class="marketplace-details shadcn admin-marketplace">
    @if(empty($module))
        <x-admin::alert type="danger" withClose="false">
            {{ __('admin-marketplace.labels.no_modules_found') }}
        </x-admin::alert>
    @else
        <div class="details-header card-gradient {{ !empty($module['isPaid']) ? 'paid' : '' }}">
            <div class="cover">
                @if(! empty($module['primaryImage']))
                    <img loading="lazy" data-src="{{ str_starts_with($module['primaryImage'], 'http') ? $module['primaryImage'] : config('app.flute_market_url').$module['primaryImage'] }}" src="{{ str_starts_with($module['primaryImage'], 'http') ? $module['primaryImage'] : config('app.flute_market_url').$module['primaryImage'] }}" alt="{{ $module['name'] }}">
                @else
                    <div class="placeholder-icon">
                        <x-icon path="ph.regular.package" />
                    </div>
                @endif
            </div>
            <div class="info">
                <h1 class="title">{{ $module['name'] }}</h1>
                <div class="labels">
                    @if(! empty($module['isPaid']))
                        <span class="chip accent">{{ __('admin-marketplace.labels.paid') }}</span>
                    @else
                        <span class="chip">{{ __('admin-marketplace.labels.free') }}</span>
                    @endif
                    @if(! empty($module['currentVersion']))
                        <span class="chip">v{{ $module['currentVersion'] }}</span>
                    @endif
                    @if(! empty($module['downloadCount']))
                        <span class="chip">{{ $module['downloadCount'] }} {{ __('admin-marketplace.labels.downloads') }}</span>
                    @endif
                </div>

                <div class="meta-grid">
                    <div class="meta-item">
                        <span class="label">{{ __('admin-marketplace.labels.author') }}</span>
                        <span class="value">{{ $module['author'] ?? 'Flames' }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="label">{{ __('admin-marketplace.labels.version') }}</span>
                        <span class="value">{{ $module['currentVersion'] ?? '—' }}</span>
                    </div>
                    @if(! empty($module['requires']))
                        <div class="meta-item">
                            <span class="label">{{ __('admin-marketplace.labels.dependencies') }}</span>
                            <span class="value">{{ is_array($module['requires']) ? implode(', ', array_keys($module['requires'])) : $module['requires'] }}</span>
                        </div>
                    @endif
                </div>

                @if(! empty($module['downloadUrl']))
                    <div class="actions">
                        <x-admin::button href="{{ url(config('app.flute_market_url').'/product/'.$module['slug']) }}" target="_blank" withoutHtmx type="secondary">
                            {{ __('admin-marketplace.actions.details') }}
                        </x-admin::button>
                        @if ($needsUpdate)
                            <x-admin::button yoyo:post="installModule('{{ $module['slug'] }}')" hx-trigger="confirmed"
                                             hx-flute-confirm="{{ __('admin-marketplace.messages.update_confirm', ['module' => $module['name']]) }}"
                                             hx-flute-confirm-title="{{ __('admin-marketplace.messages.update_confirm_title') }}"
                                             hx-flute-confirm-type="warning"
                                             type="warning"
                            >
                                {{ __('admin-marketplace.actions.update') }}
                            </x-admin::button>
                        @elseif (!$isInstalled)
                            <x-admin::button yoyo:post="installModule('{{ $module['slug'] }}')" hx-trigger="confirmed"
                                             hx-flute-confirm="{{ __('admin-marketplace.messages.install_confirm', ['module' => $module['name']]) }}"
                                             hx-flute-confirm-title="{{ __('admin-marketplace.messages.install_confirm_title') }}"
                                             hx-flute-confirm-type="warning"
                                             type="primary"
                            >
                                {{ __('admin-marketplace.actions.install') }}
                            </x-admin::button>
                        @else
                            <x-admin::button type="success" disabled>
                                {{ __('admin-marketplace.actions.installed') }}
                            </x-admin::button>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="details-body">
            <div class="tabs card-gradient">
                @php $rawDesc = $module['description'] ?? ''; @endphp
                <input type="radio" id="tab-overview" name="md-tabs" checked>
                <label for="tab-overview">{{ __('admin-marketplace.labels.overview') ?? 'Обзор' }}</label>
                @if(! empty($versions))
                    <input type="radio" id="tab-versions" name="md-tabs">
                    <label for="tab-versions">{{ __('admin-marketplace.labels.version_history') }}</label>
                @endif

                <div class="tab-content">
                    <section class="tab t-overview">
                        @if($rawDesc)
                            <div class="markdown-content md-content">
                                {!! markdown()->parse($rawDesc) !!}
                            </div>
                        @else
                            <div class="markdown-content">
                                <p class="muted">{{ __('admin-marketplace.messages.no_description') }}</p>
                            </div>
                        @endif
                    </section>
                    @if(! empty($versions))
                        <section class="tab t-versions">
                            <ul class="version-list">
                                @foreach($versions as $version)
                                    <li>
                                        <span class="ver">{{ $version['version'] }}</span>
                                        <span class="muted">{{ $version['date'] ?? '' }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
