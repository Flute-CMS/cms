<div class="marketplace-details shadcn admin-marketplace">
    @if(empty($module))
        <x-admin::alert type="danger" withClose="false">
            {{ __('admin-marketplace.labels.no_modules_found') }}
        </x-admin::alert>
    @else
        @if (!ioncube_loaded())
            <x-admin::alert type="warning" withClose="false">
                <strong>{{ __('admin-marketplace.ioncube.missing_title') }}</strong>
                <div class="mt-1">{{ __('admin-marketplace.ioncube.missing_desc') }}</div>
                <div class="mt-1 text-sm">
                    <a href="https://www.ioncube.com/loaders.php" target="_blank" rel="noreferrer">https://www.ioncube.com/loaders.php</a>
                </div>
            </x-admin::alert>
        @endif

        @if ($isLoading)
            <div class="mp-loading-overlay">
                <div class="mp-loading-spinner"></div>
                <span>{{ __('admin-marketplace.messages.loading') }}</span>
            </div>
        @endif

        <div class="mp-product">
            {{-- Header Section --}}
            <div class="mp-product-header card-gradient {{ !empty($module['isPaid']) ? 'paid' : '' }}">
                <div class="mp-product-cover">
                    @if(! empty($module['primaryImage']))
                        <img loading="lazy" src="{{ str_starts_with($module['primaryImage'], 'http') ? $module['primaryImage'] : config('app.flute_market_url').$module['primaryImage'] }}" alt="{{ $module['name'] }}">
                    @else
                        <div class="mp-product-placeholder">
                            <x-icon path="ph.regular.package" />
                        </div>
                    @endif
                    
                    {{-- Status badges --}}
                    <div class="mp-product-badges">
                        @if ($needsUpdate)
                            <span class="mp-badge warning">
                                <x-icon path="ph.bold.arrow-circle-up-bold" />
                                {{ __('admin-marketplace.labels.updates_available') }}
                            </span>
                        @elseif($isInstalled)
                            <span class="mp-badge success">
                                <x-icon path="ph.bold.check-circle-bold" />
                                {{ __('admin-marketplace.actions.installed') }}
                            </span>
                        @endif
                    </div>
                </div>
                
                <div class="mp-product-info">
                    <div class="mp-product-title-row">
                        <h1 class="mp-product-title">{{ $module['name'] }}</h1>
                        @if(! empty($module['isPaid']))
                            <span class="mp-chip accent">
                                <x-icon path="ph.bold.crown-bold" />
                                {{ __('admin-marketplace.labels.paid') }}
                            </span>
                        @else
                            <span class="mp-chip success">
                                <x-icon path="ph.bold.gift-bold" />
                                {{ __('admin-marketplace.labels.free') }}
                            </span>
                        @endif
                    </div>
                    
                    <div class="mp-product-meta">
                        <div class="mp-meta-item">
                            <x-icon path="ph.regular.user" />
                            <span>{{ $module['author'] ?? 'Flames' }}</span>
                        </div>
                        @if(! empty($module['currentVersion']))
                            <div class="mp-meta-item">
                                <x-icon path="ph.regular.tag" />
                                <span>v{{ $module['currentVersion'] }}</span>
                            </div>
                        @endif
                        @if(! empty($module['downloadCount']))
                            <div class="mp-meta-item">
                                <x-icon path="ph.regular.download-simple" />
                                <span>{{ number_format($module['downloadCount']) }} {{ __('admin-marketplace.labels.downloads') }}</span>
                            </div>
                        @endif
                    </div>
                    
                    {{-- Requirements section --}}
                    @if(! empty($module['requires']))
                        <div class="mp-product-requirements">
                            <span class="mp-requirements-label">{{ __('admin-marketplace.labels.dependencies') }}:</span>
                            <div class="mp-requirements-list">
                                @if(is_array($module['requires']))
                                    @foreach($module['requires'] as $key => $value)
                                        <span class="mp-requirement-chip">{{ $key }}: {{ $value }}</span>
                                    @endforeach
                                @else
                                    <span class="mp-requirement-chip">{{ $module['requires'] }}</span>
                                @endif
                            </div>
                        </div>
                    @endif
                    
                    {{-- Version info if installed --}}
                    @if($isInstalled && !empty($module['installedVersion']))
                        <div class="mp-version-info">
                            <div class="mp-version-row">
                                <span class="mp-version-label">{{ __('admin-marketplace.labels.version') }} {{ __('admin-marketplace.actions.installed') }}:</span>
                                <span class="mp-version-value">v{{ $module['installedVersion'] }}</span>
                            </div>
                            @if(!empty($module['currentVersion']) && $module['currentVersion'] !== $module['installedVersion'])
                                <div class="mp-version-row">
                                    <span class="mp-version-label">{{ __('admin-marketplace.labels.version') }} ({{ __('admin-marketplace.labels.new') }}):</span>
                                    <span class="mp-version-value new">v{{ $module['currentVersion'] }}</span>
                                </div>
                            @endif
                        </div>
                    @endif
                    
                    {{-- Actions --}}
                    <div class="mp-product-actions">
                        @if(! empty($module['slug']))
                            <a href="{{ config('app.flute_market_url') }}/product/{{ $module['slug'] }}" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="mp-btn mp-btn-secondary">
                                <x-icon path="ph.bold.arrow-square-out-bold" />
                                {{ __('admin-marketplace.actions.view_on_marketplace') }}
                            </a>
                        @endif
                        
                        @if(! empty($module['downloadUrl']))
                            @if ($needsUpdate)
                                <button yoyo:post="installModule('{{ $module['slug'] }}')" 
                                        hx-trigger="confirmed"
                                        hx-flute-confirm="{{ __('admin-marketplace.messages.update_confirm', ['module' => $module['name']]) }}"
                                        hx-flute-confirm-title="{{ __('admin-marketplace.messages.update_confirm_title') }}"
                                        hx-flute-confirm-type="warning"
                                        class="mp-btn mp-btn-warning">
                                    <x-icon path="ph.bold.arrow-circle-up-bold" />
                                    {{ __('admin-marketplace.actions.update') }}
                                </button>
                            @elseif (!$isInstalled)
                                <button yoyo:post="installModule('{{ $module['slug'] }}')" 
                                        hx-trigger="confirmed"
                                        hx-flute-confirm="{{ __('admin-marketplace.messages.install_confirm', ['module' => $module['name']]) }}"
                                        hx-flute-confirm-title="{{ __('admin-marketplace.messages.install_confirm_title') }}"
                                        hx-flute-confirm-type="warning"
                                        class="mp-btn mp-btn-primary">
                                    <x-icon path="ph.bold.download-simple-bold" />
                                    {{ __('admin-marketplace.actions.install') }}
                                </button>
                            @else
                                @php
                                    $moduleStatus = $status ?? '';
                                    if ($moduleStatus === '') {
                                        $mm = app(\Flute\Core\ModulesManager\ModuleManager::class);
                                        $moduleInfo = $mm->getModule($module['name'] ?? '');
                                        $moduleStatus = $moduleInfo->status ?? 'disabled';
                                    }
                                @endphp
                                
                                @if ($moduleStatus === 'active')
                                    <button yoyo:post="deactivateModule('{{ $module['name'] }}')" 
                                            hx-trigger="confirmed"
                                            hx-flute-confirm="{{ __('admin-marketplace.messages.deactivate_confirm', ['module' => $module['name']]) }}"
                                            hx-flute-confirm-title="{{ __('admin-marketplace.messages.deactivate_confirm_title') }}"
                                            hx-flute-confirm-type="warning"
                                            class="mp-btn mp-btn-secondary">
                                        <x-icon path="ph.bold.pause-circle-bold" />
                                        {{ __('admin-marketplace.actions.deactivate') }}
                                    </button>
                                @else
                                    <button yoyo:post="activateModule('{{ $module['name'] }}')" 
                                            hx-trigger="confirmed"
                                            hx-flute-confirm="{{ __('admin-marketplace.messages.activate_confirm', ['module' => $module['name']]) }}"
                                            hx-flute-confirm-title="{{ __('admin-marketplace.messages.activate_confirm_title') }}"
                                            hx-flute-confirm-type="warning"
                                            class="mp-btn mp-btn-success">
                                        <x-icon path="ph.bold.play-circle-bold" />
                                        {{ __('admin-marketplace.actions.activate') }}
                                    </button>
                                @endif
                                
                                <button yoyo:post="uninstallModule('{{ $module['name'] }}')" 
                                        hx-trigger="confirmed"
                                        hx-flute-confirm="{{ __('admin-marketplace.messages.uninstall_confirm', ['module' => $module['name']]) }}"
                                        hx-flute-confirm-title="{{ __('admin-marketplace.messages.uninstall_confirm_title') }}"
                                        hx-flute-confirm-type="danger"
                                        class="mp-btn mp-btn-danger">
                                    <x-icon path="ph.bold.trash-bold" />
                                    {{ __('admin-marketplace.actions.uninstall') }}
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            
            {{-- Gallery Section --}}
            @php
                $images = $module['images'] ?? [];
                if (!empty($module['primaryImage'])) {
                    $primaryImage = $module['primaryImage'];
                    if (!in_array($primaryImage, $images)) {
                        array_unshift($images, $primaryImage);
                    }
                }
            @endphp
            @if(count($images) > 0)
                <div class="mp-gallery card-gradient">
                    <h3 class="mp-section-title">
                        <x-icon path="ph.regular.images" />
                        {{ __('admin-marketplace.labels.features') }}
                    </h3>
                    <div class="mp-gallery-grid" id="mp-gallery-grid">
                        @foreach($images as $index => $image)
                            @php
                                $imgUrl = str_starts_with($image, 'http') ? $image : rtrim(config('app.flute_market_url'), '/').$image;
                            @endphp
                            <div class="mp-gallery-item" data-index="{{ $index }}" data-src="{{ $imgUrl }}">
                                <img loading="lazy" 
                                     src="{{ $imgUrl }}" 
                                     alt="{{ $module['name'] }} - {{ $index + 1 }}">
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            
            {{-- Content Tabs --}}
            <div class="mp-content card-gradient">
                <div class="mp-tabs">
                    <input type="radio" id="mp-tab-overview" name="mp-tabs" checked>
                    <label for="mp-tab-overview">
                        <x-icon path="ph.regular.article" />
                        {{ __('admin-marketplace.labels.overview') }}
                    </label>
                    
                    @if(! empty($versions))
                        <input type="radio" id="mp-tab-versions" name="mp-tabs">
                        <label for="mp-tab-versions">
                            <x-icon path="ph.regular.git-branch" />
                            {{ __('admin-marketplace.labels.version_history') }}
                        </label>
                    @endif
                </div>
                
                <div class="mp-tab-content">
                    {{-- Overview Tab --}}
                    <section class="mp-tab mp-tab-overview">
                        @php $rawDesc = $module['description'] ?? ''; @endphp
                        @if($rawDesc)
                            <div class="mp-description">
                                {!! markdown()->parse($rawDesc) !!}
                            </div>
                        @else
                            <div class="mp-empty-state">
                                <x-icon path="ph.regular.note-blank" />
                                <p>{{ __('admin-marketplace.messages.no_description') }}</p>
                            </div>
                        @endif
                    </section>
                    
                    {{-- Versions Tab --}}
                    @if(! empty($versions))
                        <section class="mp-tab mp-tab-versions">
                            <div class="mp-versions-list">
                                @foreach($versions as $version)
                                    <div class="mp-version-item">
                                        <div class="mp-version-header">
                                            <span class="mp-version-number">
                                                <x-icon path="ph.bold.tag-bold" />
                                                v{{ $version['version'] }}
                                            </span>
                                            @if(!empty($version['date']))
                                                <span class="mp-version-date">{{ $version['date'] }}</span>
                                            @endif
                                        </div>
                                        @if(!empty($version['changes']) || !empty($version['description']))
                                            <div class="mp-version-changes">
                                                {!! markdown()->parse($version['changes'] ?? $version['description'] ?? '') !!}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>
            </div>
        </div>
        
        {{-- Lightbox for gallery --}}
        <div class="mp-lightbox" id="mp-lightbox">
            <button class="mp-lightbox-close" type="button">
                <x-icon path="ph.bold.x-bold" />
            </button>
            <button class="mp-lightbox-prev" type="button">
                <x-icon path="ph.bold.caret-left-bold" />
            </button>
            <button class="mp-lightbox-next" type="button">
                <x-icon path="ph.bold.caret-right-bold" />
            </button>
            <div class="mp-lightbox-content">
                <img src="" alt="">
            </div>
        </div>
    @endif
</div>

<script>
(function() {
    function initGalleryLightbox() {
        const gallery = document.querySelectorAll('.mp-gallery-item');
        const lightbox = document.getElementById('mp-lightbox');
        
        if (!gallery.length || !lightbox) return;
        
        const lightboxImg = lightbox.querySelector('.mp-lightbox-content img');
        const closeBtn = lightbox.querySelector('.mp-lightbox-close');
        const prevBtn = lightbox.querySelector('.mp-lightbox-prev');
        const nextBtn = lightbox.querySelector('.mp-lightbox-next');
        let currentIndex = 0;
        
        const images = Array.from(gallery).map(item => {
            return item.getAttribute('data-src') || item.querySelector('img')?.src || '';
        }).filter(src => src);
        
        if (!images.length) return;
        
        function openLightbox(index) {
            currentIndex = index;
            lightboxImg.src = images[currentIndex];
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeLightbox() {
            lightbox.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        function showPrev() {
            currentIndex = (currentIndex - 1 + images.length) % images.length;
            lightboxImg.src = images[currentIndex];
        }
        
        function showNext() {
            currentIndex = (currentIndex + 1) % images.length;
            lightboxImg.src = images[currentIndex];
        }
        
        gallery.forEach((item, index) => {
            item.style.cursor = 'pointer';
            item.addEventListener('click', () => openLightbox(index));
        });
        
        if (closeBtn) closeBtn.addEventListener('click', closeLightbox);
        if (prevBtn) prevBtn.addEventListener('click', showPrev);
        if (nextBtn) nextBtn.addEventListener('click', showNext);
        
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) closeLightbox();
        });
        
        document.addEventListener('keydown', (e) => {
            if (!lightbox.classList.contains('active')) return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') showPrev();
            if (e.key === 'ArrowRight') showNext();
        });
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGalleryLightbox);
    } else {
        initGalleryLightbox();
    }
    
    document.addEventListener('htmx:afterSwap', initGalleryLightbox);
    document.addEventListener('htmx:afterSettle', initGalleryLightbox);
})();
</script>
