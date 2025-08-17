const { computePosition, offset, flip, shift, autoUpdate } =
    window.FloatingUIDOM || {};

document.addEventListener('DOMContentLoaded', () => {
    if (!window.FloatingUIDOM) {
        console.error("FloatingUIDOM library is not loaded");
        return;
    }
    
    const miniProfileEl = document.getElementById('user-card');
    const miniProfileInner = document.getElementById('user-card-inner');
    
    if (!miniProfileEl || !miniProfileInner) {
        return;
    }
    
    const overlay = document.createElement('div');
    overlay.className = 'user-card-overlay';
    document.body.appendChild(overlay);
    
    let activeTrigger = null;
    let hoverTimeout = null;
    let hideTimeout = null;
    let cleanupAutoUpdate = null;
    let isAnimating = false;
    const profileCache = {};
    let cacheExpiry = {};
    let currentPlacement = 'right';
    let interactionType = 'hover'; // 'hover' or 'click'
    
    const CACHE_LIFETIME = 5 * 60 * 1000; // 5 minutes
    const HOVER_DELAY = 200;
    const HOVER_OUT_DELAY = 200;

    miniProfileEl.setAttribute('aria-hidden', 'true');
    miniProfileEl.setAttribute('tabindex', '-1');
    
    miniProfileEl.style.transform = 'scale(1)';

    function resetPlacementClasses(el) {
        el.classList.remove(
            'from-right',
            'from-left',
            'from-top',
            'from-bottom',
        );
    }

    function applyPlacementClass(el, placement) {
        resetPlacementClasses(el);
        
        if (placement.startsWith('right')) {
            el.classList.add('from-right');
        } else if (placement.startsWith('left')) {
            el.classList.add('from-left');
        } else if (placement.startsWith('top')) {
            el.classList.add('from-top');
        } else if (placement.startsWith('bottom')) {
            el.classList.add('from-bottom');
        } else {
            el.classList.add('from-right');
        }
    }

    function getDominantColor(image, callback) {
        if (!image || !image.complete) {
            callback(null);
            return;
        }
        
        requestAnimationFrame(() => {
            try {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d', { willReadFrequently: true });
                canvas.width = Math.min(image.naturalWidth || image.width, 50);
                canvas.height = Math.min(image.naturalHeight || image.height, 50);
                ctx.drawImage(image, 0, 0, canvas.width, canvas.height);
                const data = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
                const c = { r: 0, g: 0, b: 0, count: 0 };
                
                for (let i = 0; i < data.length; i += 16) {
                    c.r += data[i];
                    c.g += data[i + 1];
                    c.b += data[i + 2];
                    c.count++;
                }
                
                c.r = Math.floor(c.r / c.count);
                c.g = Math.floor(c.g / c.count);
                c.b = Math.floor(c.b / c.count);
                callback(`rgb(${c.r}, ${c.g}, ${c.b})`);
            } catch (e) {
                callback(null);
            }
        });
    }

    function generateDynamicBackground(bannerEl) {
        const bodyBg = getComputedStyle(document.body).backgroundColor;
        
        if (!bannerEl) {
            miniProfileEl.style.setProperty('--dynamic-bg', bodyBg);
            miniProfileEl.style.setProperty(
                '--dynamic-border',
                'rgba(0, 0, 0, 0.1)',
            );
            return;
        }
        
        if (bannerEl.complete) {
            getDominantColor(bannerEl, (dominantColor) => {
                if (!dominantColor) {
                    miniProfileEl.style.setProperty('--dynamic-bg', bodyBg);
                    return;
                }
                
                miniProfileEl.style.setProperty(
                    '--dynamic-bg',
                    `linear-gradient(to bottom, ${dominantColor}, ${bodyBg})`,
                );
                miniProfileEl.style.setProperty(
                    '--dynamic-border',
                    dominantColor,
                );
            });
        } else {
            bannerEl.addEventListener('load', () => {
                getDominantColor(bannerEl, (dominantColor) => {
                    if (!dominantColor) return;
                    
                    miniProfileEl.style.setProperty(
                        '--dynamic-bg',
                        `linear-gradient(to bottom, ${dominantColor}, ${bodyBg})`,
                    );
                    miniProfileEl.style.setProperty(
                        '--dynamic-border',
                        dominantColor,
                    );
                });
            }, { once: true });
            
            miniProfileEl.style.setProperty('--dynamic-bg', bodyBg);
            miniProfileEl.style.setProperty(
                '--dynamic-border',
                'rgba(0, 0, 0, 0.1)',
            );
        }
    }

    function showOverlay() {
        overlay.classList.add('active');
    }

    function hideOverlay() {
        overlay.classList.remove('active');
    }

    function hideMiniProfile() {
        if (!miniProfileEl.classList.contains('active') || isAnimating) return;
        
        isAnimating = true;
        miniProfileEl.classList.remove('active');
        miniProfileEl.classList.add('hide');
        miniProfileEl.setAttribute('aria-hidden', 'true');
        hideOverlay();
        
        const finishHide = () => {
            if (miniProfileEl.classList.contains('hide')) {
                miniProfileInner.innerHTML = '';
                miniProfileEl.classList.remove('hide');
                miniProfileEl.style.top = '0';
                miniProfileEl.style.left = '0';
                resetPlacementClasses(miniProfileEl);
            }
            isAnimating = false;
        };

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            finishHide();
        } else {
            miniProfileEl.addEventListener('animationend', finishHide, {
                once: true,
            });
        }
        
        if (cleanupAutoUpdate) {
            cleanupAutoUpdate();
            cleanupAutoUpdate = null;
        }
        
        activeTrigger = null;
        interactionType = 'hover';
    }

    async function showMiniProfile(triggerEl, username, type = 'hover') {
        if (isAnimating) return;
        
        if (!document.body.contains(triggerEl)) {
            if (miniProfileEl.classList.contains('active')) {
                hideMiniProfile();
            }
            return;
        }
        
        if (activeTrigger === triggerEl && miniProfileEl.classList.contains('active')) {
            hideMiniProfile();
            return;
        }
        
        clearTimeout(hideTimeout);
        interactionType = type;
        
        if (activeTrigger && activeTrigger !== triggerEl) {
            if (miniProfileEl.classList.contains('active')) {
                hideMiniProfile();
                await new Promise(resolve => setTimeout(resolve, 200));
            } else {
                miniProfileEl.classList.remove('active', 'hide');
                resetPlacementClasses(miniProfileEl);
                miniProfileInner.innerHTML = '';
                if (cleanupAutoUpdate) {
                    cleanupAutoUpdate();
                    cleanupAutoUpdate = null;
                }
            }
        }
        
        activeTrigger = triggerEl;
        
        if (type === 'click') {
            showOverlay();
        }
        
        miniProfileInner.innerHTML = `
            <div class="uc-skeleton">
                <div class="user-card-header">
                    <div class="user-card-banner">
                        <div class="skeleton uc-sk-banner"></div>
                    </div>
                    <div class="user-card-avatar">
                        <div class="skeleton uc-sk-avatar"></div>
                    </div>
                </div>
                <div class="user-card-body">
                    <div class="user-card-info">
                        <div class="skeleton uc-sk-name"></div>
                        <div class="skeleton uc-sk-sub"></div>
                    </div>
                    <div class="user-card-roles uc-sk-roles">
                        <div class="skeleton uc-sk-role-pill"></div>
                        <div class="skeleton uc-sk-role-dot"></div>
                        <div class="skeleton uc-sk-role-dot"></div>
                        <div class="skeleton uc-sk-role-dot"></div>
                    </div>
                    <div class="user-card-goto">
                        <div class="skeleton uc-sk-button"></div>
                    </div>
                </div>
            </div>`;
        miniProfileEl.classList.remove('hide');
        isAnimating = true;
        
        try {
            if (!document.body.contains(triggerEl)) {
                throw new Error("Trigger element removed from DOM");
            }
            
            const { placement } = await updatePosition(triggerEl);
            currentPlacement = placement;
            applyPlacementClass(miniProfileEl, placement);
            miniProfileEl.classList.add('active', 'showing');
            miniProfileEl.setAttribute('aria-hidden', 'false');
            
            let html;
            try {
                html = await loadMiniProfile(username);
                if (html.includes('error-message')) {
                    throw new Error('Failed to load profile data');
                }
            } catch (e) {
                hideMiniProfile();
                isAnimating = false;
                return;
            }
            
            if (!document.body.contains(triggerEl) || activeTrigger !== triggerEl) {
                hideMiniProfile();
                isAnimating = false;
                return;
            }
            
            miniProfileInner.innerHTML = html;
            if (window.htmx) {
                htmx.process(miniProfileInner);
            }
            
            const bannerEl = miniProfileEl.querySelector('.user-card-banner img');
            generateDynamicBackground(bannerEl);
            
            cleanupAutoUpdate = autoUpdate(triggerEl, miniProfileEl, async () => {
                if (!activeTrigger) return;
                
                if (!document.body.contains(triggerEl)) {
                    hideMiniProfile();
                    return;
                }
                
                try {
                    const { placement: newPlacement } = await computeAndUpdatePosition(
                        triggerEl,
                    );
                    if (newPlacement !== currentPlacement) {
                        currentPlacement = newPlacement;
                        if (!miniProfileEl.classList.contains('showing')) {
                            applyPlacementClass(miniProfileEl, newPlacement);
                        }
                    }
                } catch (error) {
                    console.error('Error updating position:', error);
                    if (!document.body.contains(triggerEl)) {
                        hideMiniProfile();
                    }
                }
            });
            
            miniProfileEl
                .querySelectorAll('.user-card-avatar, .user-card-goto > a')
                .forEach((el) => {
                    el.addEventListener('click', hideMiniProfile);
                });
            // remove showing flag after initial paint
            requestAnimationFrame(() => miniProfileEl.classList.remove('showing'));
                
        } catch (error) {
            console.error('Error showing mini profile:', error);
            hideMiniProfile();
        } finally {
            isAnimating = false;
        }
    }

    async function loadMiniProfile(username) {
        const now = Date.now();
        if (profileCache[username] && cacheExpiry[username] > now) {
            return profileCache[username];
        }
        
        try {
            const r = await fetch(u(`profile/${username}/mini`));
            if (!r.ok) {
                throw new Error(`Failed to load profile: ${r.status}`);
            }
            const t = await r.text();
            
            profileCache[username] = t;
            cacheExpiry[username] = now + CACHE_LIFETIME;
            
            return t;
        } catch (e) {
            console.error(`Error loading profile for ${username}:`, e);
            throw e;
        }
    }

    function getUsernameFromLink(link) {
        if (!link) return null;
        try {
            const u = new URL(link.href, window.location.origin);
            const p = u.pathname.split('/');
            const idx = p.findIndex((v) => v === 'profile');
            if (idx < 0) return null;
            const seg = p[idx + 1];
            if (!seg || seg === 'settings') return null;
            if (seg === 'search') {
                const val = p[idx + 2];
                return val || null;
            }
            return seg;
        } catch (e) {
            console.error('Error parsing link URL:', e);
            return null;
        }
    }

    async function updatePosition(triggerEl) {
        return computeAndUpdatePosition(triggerEl);
    }

    async function computeAndUpdatePosition(triggerEl) {
        try {
            if (!document.body.contains(triggerEl)) {
                throw new Error("Trigger element is not in the DOM");
            }
            
            const r = await computePosition(triggerEl, miniProfileEl, {
                placement: 'right',
                middleware: [offset(10), flip(), shift({ padding: 8 })],
            });
            
            const { x, y, placement } = r;
            
            miniProfileEl.style.left = `${x}px`;
            miniProfileEl.style.top = `${y}px`;
            miniProfileEl.style.transform = 'scale(1)';
            
            return { x, y, placement };
        } catch (error) {
            console.error('Error computing position:', error);
            if (!document.body.contains(triggerEl) && miniProfileEl.classList.contains('active')) {
                hideMiniProfile();
            }
            return { x: 0, y: 0, placement: 'right' };
        }
    }
    
    const debounce = (fn, delay) => {
        let timer = null;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn(...args), delay);
        };
    };

    document.addEventListener('mouseover', debounce((e) => {
        if (interactionType === 'click') return;
        
        const linkEl = e.target.closest('a[data-user-card][href*="/profile/"]');
        if (
            !linkEl ||
            linkEl === activeTrigger ||
            miniProfileEl.contains(e.target)
        ) return;
        
        clearTimeout(hoverTimeout);
        hoverTimeout = setTimeout(async () => {
            const username = getUsernameFromLink(linkEl);
            if (!username) return;
            try {
                await showMiniProfile(linkEl, username, 'hover');
            } catch (error) {
                console.error('Error in hover handler:', error);
            }
        }, HOVER_DELAY);
    }, 50));

    document.addEventListener('click', (e) => {
        const linkEl = e.target.closest('a[data-user-card][href*="/profile/"]');
        if (linkEl) {
            if (linkEl.hasAttribute('hx-boost')) return;
            e.preventDefault();
            const username = getUsernameFromLink(linkEl);
            if (username) showMiniProfile(linkEl, username, 'click');
            return;
        }
        
        if (miniProfileEl.classList.contains('active') && 
            !miniProfileEl.contains(e.target) && 
            !activeTrigger?.contains(e.target)) {
            hideMiniProfile();
            return;
        }
    });

    document.addEventListener('mouseout', (e) => {
        if (interactionType === 'click') return;
        
        clearTimeout(hoverTimeout);
        const r = e.relatedTarget;
        if (
            activeTrigger &&
            !miniProfileEl.contains(r) &&
            !activeTrigger.contains(r)
        ) {
            hideTimeout = setTimeout(() => {
                if (!miniProfileEl.matches(':hover')) {
                    hideMiniProfile();
                }
            }, HOVER_OUT_DELAY);
        }
    });

    miniProfileEl.addEventListener('mouseleave', () => {
        if (interactionType === 'click') return;
        
        hideTimeout = setTimeout(() => {
            if (
                !miniProfileEl.matches(':hover') &&
                !activeTrigger?.matches(':hover')
            ) {
                hideMiniProfile();
            }
        }, HOVER_OUT_DELAY);
    });

    miniProfileEl.addEventListener('mouseenter', () => {
        clearTimeout(hideTimeout);
    });
    
    overlay.addEventListener('click', () => {
        if (miniProfileEl.classList.contains('active')) {
            hideMiniProfile();
        }
    });
    
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && miniProfileEl.classList.contains('active')) {
            hideMiniProfile();
        }
    });
    
    document.addEventListener('focusin', (e) => {
        const cardLinks = Array.from(miniProfileEl.querySelectorAll('a, button, [tabindex="0"]'));
        
        if (miniProfileEl.classList.contains('active') && 
            !miniProfileEl.contains(e.target) && 
            !cardLinks.includes(e.target)) {
            hideMiniProfile();
        }
    });
    
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden' && miniProfileEl.classList.contains('active')) {
            hideMiniProfile();
        }
    });
    
    window.addEventListener('beforeunload', () => {
        if (cleanupAutoUpdate) {
            cleanupAutoUpdate();
        }
    });
    
    function setupTriggerChecking() {
        setInterval(() => {
            if (activeTrigger && !document.body.contains(activeTrigger) && miniProfileEl.classList.contains('active')) {
                hideMiniProfile();
            }
        }, 1000);
    }
    
    setupTriggerChecking();
});
