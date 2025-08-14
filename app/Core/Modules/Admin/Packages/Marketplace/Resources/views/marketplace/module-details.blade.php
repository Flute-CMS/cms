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
            <div class="meta">
                {{-- Title is shown by the Screen header; avoid duplication here --}}
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
                @if(! empty($module['downloadUrl']))
                    <div class="actions">
                        <x-admin::button href="{{ url(config('app.flute_market_url').'/product/'.$module['slug']) }}" target="_blank" withoutHtmx type="primary">
                            {{ __('admin-marketplace.actions.details') }}
                        </x-admin::button>
                    </div>
                @endif
            </div>
        </div>

        <div class="details-body">
            <div class="left">
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
                                <div class="markdown-content">
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
            <div class="right">
                <div class="meta-cards card-gradient">
                    <div class="card-row">
                        <span>{{ __('admin-marketplace.labels.author') }}</span>
                        <span class="muted text-sm">{{ $module['author'] ?? '—' }}</span>
                    </div>
                    <div class="card-row">
                        <span>{{ __('admin-marketplace.labels.downloads') }}</span>
                        <span class="muted text-sm">{{ $module['downloadCount'] ?? 0 }}</span>
                    </div>
                    @if(! empty($module['requires']))
                        <div class="card-row">
                            <span>{{ __('admin-marketplace.labels.dependencies') }}</span>
                            <span class="muted text-sm">{{ is_array($module['requires']) ? implode(', ', array_keys($module['requires'])) : $module['requires'] }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
