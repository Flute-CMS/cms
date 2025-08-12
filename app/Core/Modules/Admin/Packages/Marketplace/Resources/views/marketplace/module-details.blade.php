<div class="marketplace-details shadcn admin-marketplace">
    @if(empty($module))
        <x-admin::alert type="danger" withClose="false">
            {{ __('admin-marketplace.labels.no_modules_found') }}
        </x-admin::alert>
    @else
        <div class="details-header card-gradient">
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
                <h2 class="title-lg">{{ $module['name'] ?? '' }}</h2>
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
                <div class="actions">
                    <x-admin::button href="{{ url('/admin/marketplace') }}" type="outline-primary">
                        {{ __('admin-marketplace.actions.back_to_list') }}
                    </x-admin::button>
                    @if(! empty($module['downloadUrl']))
                        <x-admin::button href="{{ url(config('app.flute_market_url').'/product/'.$module['slug']) }}" target="_blank" withoutHtmx type="primary">
                            {{ __('admin-marketplace.actions.details') }}
                        </x-admin::button>
                    @endif
                </div>
            </div>
        </div>

        <div class="details-body">
            <div class="left">
                @php $rawDesc = $module['description'] ?? ''; @endphp
                @if($rawDesc)
                    <div class="markdown-content">
                        {!! markdown()->parse($rawDesc) !!}
                    </div>
                @endif

                @if(! empty($versions))
                    <div class="versions">
                        <h3>{{ __('admin-marketplace.labels.version_history') }}</h3>
                        <ul>
                            @foreach($versions as $version)
                                <li>
                                    <span>{{ $version['version'] }}</span>
                                    <span class="muted">{{ $version['date'] ?? '' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
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

