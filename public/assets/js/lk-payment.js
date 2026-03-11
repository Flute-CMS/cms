(function () {
    'use strict';

    var root = document.getElementById('lk-app');
    if (!root) return;

    var configRaw = root.getAttribute('data-config');
    if (!configRaw) return;

    var cfg;
    try { cfg = JSON.parse(configRaw); } catch (e) { return; }

    var form = document.getElementById('lk-form');
    if (!form) return;

    var isPage = cfg.mode === 'page';

    // ── State ──────────────────────────────────────────────
    var state = {
        currency: '',
        gateway: '',
        amount: 0,
        promoCode: '',
        promoValid: false,
        promoDetails: null,
        agree: !cfg.ofertaView
    };

    // ── DOM refs ───────────────────────────────────────────
    var amountInput = document.getElementById('lk-amount');
    var currencyLabel = root.querySelector('[data-lk-currency-label]');
    var hintEl = root.querySelector('[data-lk-hint]');
    var presetsWrap = root.querySelector('[data-lk-presets]');
    var receiptEl = root.querySelector('[data-lk-receipt]');
    var promoInput = document.getElementById('lk-promo');
    var promoWrap = root.querySelector('[data-lk-promo]');
    var promoBadge = root.querySelector('[data-lk-promo-badge]');
    var submitBtn = document.getElementById('lk-submit');
    var agreeCheckbox = document.getElementById('lk-agree');

    // ── Helpers ────────────────────────────────────────────
    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    function resolveUrl(path) {
        if (typeof u === 'function') {
            return u(path);
        }

        var baseMeta = document.querySelector('meta[name="site_url"]');
        var base = baseMeta ? baseMeta.content : window.location.origin;
        base = String(base || '').replace(/\/+$/, '');
        path = String(path || '').replace(/^\/+/, '');

        return base + '/' + path;
    }

    function tryParseJson(text) {
        if (!text) {
            return {};
        }

        var normalized = String(text).replace(/^\uFEFF/, '').trim();
        if (!normalized) {
            return {};
        }

        return JSON.parse(normalized);
    }

    function extractJsonCandidate(text) {
        var normalized = String(text || '').replace(/^\uFEFF/, '').trim();
        if (!normalized) {
            return '';
        }

        var start = normalized.search(/[\{\[]/);
        if (start === -1) {
            return '';
        }

        var openChar = normalized.charAt(start);
        var closeChar = openChar === '{' ? '}' : ']';
        var depth = 0;
        var inString = false;
        var isEscaped = false;

        for (var i = start; i < normalized.length; i++) {
            var ch = normalized.charAt(i);

            if (inString) {
                if (isEscaped) {
                    isEscaped = false;
                    continue;
                }

                if (ch === '\\') {
                    isEscaped = true;
                    continue;
                }

                if (ch === '"') {
                    inString = false;
                }

                continue;
            }

            if (ch === '"') {
                inString = true;
                continue;
            }

            if (ch === openChar) {
                depth++;
                continue;
            }

            if (ch === closeChar) {
                depth--;
                if (depth === 0) {
                    return normalized.slice(start, i + 1);
                }
            }
        }

        return '';
    }

    function parseJsonResponse(response) {
        return response.text().then(function (text) {
            var data = {};

            if (text) {
                try {
                    data = tryParseJson(text);
                } catch (e) {
                    var candidate = extractJsonCandidate(text);

                    if (candidate) {
                        try {
                            data = JSON.parse(candidate);

                            console.warn('Payment API response contained extra output after JSON payload.', {
                                status: response.status,
                                redirected: response.redirected,
                                url: response.url
                            });

                            return { ok: response.ok, status: response.status, data: data };
                        } catch (nestedError) {
                        }
                    }

                    var compactText = String(text || '').replace(/\s+/g, ' ').trim();
                    var preview = compactText.slice(0, 180);
                    if (preview) {
                        console.error('Payment API non-JSON response:', preview, {
                            status: response.status,
                            redirected: response.redirected,
                            url: response.url
                        });
                    }

                    var fallbackMessage = response.ok
                        ? ('Server returned invalid response' + (preview ? ': ' + preview : ''))
                        : ('HTTP ' + response.status);

                    throw new Error(fallbackMessage);
                }
            }

            return { ok: response.ok, status: response.status, data: data };
        });
    }

    function formatNumber(n, decimals) {
        if (decimals === undefined) decimals = 2;
        return Number(n).toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    function show(el) { if (el) el.style.display = ''; }
    function hide(el) { if (el) el.style.display = 'none'; }

    function debounce(fn, ms) {
        var timer;
        return function () {
            var args = arguments, ctx = this;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(ctx, args); }, ms);
        };
    }

    // ── Presets ────────────────────────────────────────────
    function getPresets(cur) {
        return cfg.presets[cur] || cfg.presets._default || [500, 1000, 2500, 5000];
    }

    function renderPresets() {
        if (!presetsWrap) return;
        var presets = getPresets(state.currency);
        presetsWrap.innerHTML = '';
        presets.forEach(function (val) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'lk-preset' + (state.amount === val ? ' is-active' : '');
            btn.setAttribute('data-amount', val);
            btn.textContent = val.toLocaleString();
            btn.addEventListener('click', function () {
                state.amount = val;
                amountInput.value = val;
                renderPresets();
                recalculate();
            });
            presetsWrap.appendChild(btn);
        });
    }

    // ── Hint ───────────────────────────────────────────────
    function updateHint() {
        if (!hintEl) return;
        var min = getEffectiveMin();
        if (min > 0) {
            var tpl = cfg.i18n.min_amount_info || '';
            hintEl.textContent = tpl.replace(':amount', min).replace(':currency', state.currency);
        } else {
            hintEl.textContent = '';
        }
    }

    function getEffectiveMin() {
        var gwData = getGatewayData();
        if (gwData && gwData.minimum_amount) {
            return Number(gwData.minimum_amount);
        }
        return Number(cfg.minimumAmounts[state.currency]) || 0;
    }

    function getGatewayData() {
        var gws = cfg.gateways[state.currency];
        if (!gws || !state.gateway) return null;
        return gws[state.gateway] || null;
    }

    // ── Gateway-specific fields ─────────────────────────────
    function updateGatewayFields(gw) {
        var containers = root.querySelectorAll('[data-lk-gw-fields]');
        containers.forEach(function (el) {
            var isMatch = el.getAttribute('data-lk-gw-fields') === gw;
            if (isMatch) {
                show(el);
                el.querySelectorAll('input, select, textarea').forEach(function (i) { i.disabled = false; });
            } else {
                hide(el);
                el.querySelectorAll('input, select, textarea').forEach(function (i) { i.disabled = true; });
            }
        });
    }

    // ── Currency switching ─────────────────────────────────
    function onCurrencyChange(cur) {
        if (cur === state.currency) return;
        state.currency = cur;

        // Show/hide gateway groups
        var allGroups = root.querySelectorAll('[data-lk-gateways]');
        allGroups.forEach(function (g) {
            var code = g.getAttribute('data-lk-gateways');
            if (code === cur) {
                show(g);
                g.querySelectorAll('input[type="radio"]').forEach(function (r) { r.disabled = false; });
            } else {
                hide(g);
                g.querySelectorAll('input[type="radio"]').forEach(function (r) { r.disabled = true; });
            }
        });

        // Enable/disable hidden gateway inputs
        root.querySelectorAll('[data-lk-gateway-hidden]').forEach(function (h) {
            var code = h.getAttribute('data-lk-gateway-hidden');
            if (code === cur) {
                h.disabled = false;
                h.name = 'gateway';
            } else {
                h.disabled = true;
                h.name = '';
            }
        });

        // Auto-select first gateway in new currency
        var activeGroup = root.querySelector('[data-lk-gateways="' + cur + '"]');
        if (activeGroup) {
            var radios = activeGroup.querySelectorAll('input[type="radio"]');
            var checked = activeGroup.querySelector('input[type="radio"]:checked');
            if (!checked && radios.length > 0) {
                radios[0].checked = true;
                state.gateway = radios[0].value;
            } else if (checked) {
                state.gateway = checked.value;
            }
        }

        // Update currency label
        if (currencyLabel) currencyLabel.textContent = cur;

        // Reset amount
        state.amount = 0;
        if (amountInput) amountInput.value = '';

        updateGatewayFields(state.gateway);
        updateHint();
        renderPresets();
        recalculate();
    }

    // ── Gateway switching ──────────────────────────────────
    function onGatewayChange(gw) {
        state.gateway = gw;
        updateGatewayFields(gw);
        updateHint();
        recalculate();
    }

    // ── Receipt calculation ────────────────────────────────
    function recalculate() {
        if (!state.amount || state.amount <= 0 || !state.gateway) {
            hide(receiptEl);
            updateSubmitState();
            return;
        }

        var exchangeRate = cfg.exchangeRates[state.currency] || 1;
        var gwData = getGatewayData();
        var fee = gwData ? Number(gwData.fee) || 0 : 0;
        var bonus = gwData ? Number(gwData.bonus) || 0 : 0;

        var amount = state.amount;
        var amountToReceive = amount * exchangeRate;
        var bonusAmount = bonus > 0 ? Math.round((amountToReceive * bonus) / 100 * 100) / 100 : 0;
        amountToReceive += bonusAmount;

        var amountToPay = amount;

        // Promo
        var promoHtml = '';
        if (state.promoValid && state.promoDetails) {
            var p = state.promoDetails;
            if (p.type === 'amount') {
                amountToReceive += p.value;
                promoHtml = '<div class="lk-receipt__row lk-receipt__row--green">' +
                    '<span>' + cfg.i18n.bonus + '</span>' +
                    '<span>+' + p.value + ' ' + state.currency + '</span></div>';
            } else if (p.type === 'percentage') {
                var discount = (amountToPay * p.value) / 100;
                amountToPay = Math.max(0, amountToPay - discount);
                promoHtml = '<div class="lk-receipt__row lk-receipt__row--green">' +
                    '<span>' + cfg.i18n.discount + '</span>' +
                    '<span>-' + p.value + '%</span></div>';
            }
        }

        // Keep the site-side amount stable. The provider may add its own
        // commission on the payment page, but that should not alter the
        // amount sent from the site.
        var totalToPay = amountToPay;

        // Build receipt HTML
        var html = '';
        html += '<div class="lk-receipt__row">';
        html += '<span>' + cfg.i18n.base_amount + '</span>';
        html += '<span>' + formatNumber(amount) + ' ' + state.currency + '</span>';
        html += '</div>';

        if (bonus > 0) {
            html += '<div class="lk-receipt__row lk-receipt__row--green">';
            html += '<span>' + cfg.i18n.gateway_bonus + ' (+' + bonus + '%)</span>';
            html += '<span>+' + formatNumber(bonusAmount, 0) + ' ' + cfg.currencyView + '</span>';
            html += '</div>';
        }

        html += promoHtml;

        if (fee > 0) {
            html += '<div class="lk-receipt__total">';
            html += '<span>' + cfg.i18n.to_pay + '</span>';
            html += '<span>' + formatNumber(totalToPay) + ' ' + state.currency + '</span>';
            html += '</div>';
        }

        html += '<div class="lk-receipt__total">';
        html += '<span>' + cfg.i18n.you_will_receive + '</span>';
        html += '<span>' + formatNumber(amountToReceive) + ' ' + cfg.currencyView + '</span>';
        html += '</div>';

        receiptEl.innerHTML = html;
        show(receiptEl);
        updateSubmitState();
    }

    // ── Submit state ───────────────────────────────────────
    function updateSubmitState() {
        if (!submitBtn) return;
        var valid = state.amount > 0
            && state.gateway
            && state.currency
            && state.agree
            && !(state.promoCode && !state.promoValid);
        submitBtn.disabled = !valid;
    }

    // ── Promo validation ───────────────────────────────────
    var validatePromoDebounced = debounce(function () {
        var code = (promoInput.value || '').trim();
        state.promoCode = code;

        if (!code) {
            state.promoValid = false;
            state.promoDetails = null;
            setPromoState('');
            recalculate();
            return;
        }

        fetch(resolveUrl('api/lk/validate-promo'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ promoCode: code, amount: state.amount })
        })
        .then(parseJsonResponse)
        .then(function (data) {
            var payload = data.data || {};

            if (payload.valid) {
                state.promoValid = true;
                state.promoDetails = { type: payload.type, value: payload.value };
                setPromoState('valid');
            } else {
                state.promoValid = false;
                state.promoDetails = null;
                setPromoState('invalid');
            }
            recalculate();
        })
        .catch(function () {
            state.promoValid = false;
            state.promoDetails = null;
            setPromoState('');
            recalculate();
        });
    }, 600);

    function setPromoState(s) {
        if (!promoWrap) return;
        promoWrap.classList.toggle('is-valid', s === 'valid');
        promoWrap.classList.toggle('is-invalid', s === 'invalid');

        if (!promoBadge) return;
        if (s === 'valid') {
            promoBadge.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 256 256"><path fill="currentColor" d="M232.49 80.49l-128 128a12 12 0 0 1-17 0l-56-56a12 12 0 1 1 17-17L96 183 215.51 63.51a12 12 0 0 1 17 17Z"/></svg>';
            promoBadge.className = 'lk-promo__badge is-valid';
            show(promoBadge);
        } else if (s === 'invalid') {
            promoBadge.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 256 256"><path fill="currentColor" d="M208.49 191.51a12 12 0 0 1-17 17L128 145l-63.51 63.49a12 12 0 0 1-17-17L111 128L47.51 64.49a12 12 0 0 1 17-17L128 111l63.51-63.49a12 12 0 0 1 17 17L145 128Z"/></svg>';
            promoBadge.className = 'lk-promo__badge is-invalid';
            show(promoBadge);
        } else {
            hide(promoBadge);
        }
    }

    // ── Collect additional form fields ─────────────────────
    function collectFormData() {
        var data = {
            gateway: state.gateway,
            currency: state.currency,
            amount: state.amount,
            promoCode: state.promoCode,
            agree: state.agree
        };

        // Collect all additional inputs from gateway-specific fields
        var activeContainer = root.querySelector('[data-lk-gw-fields="' + state.gateway + '"]');
        if (activeContainer) {
            activeContainer.querySelectorAll('input:not([disabled]), select:not([disabled]), textarea:not([disabled])').forEach(function (el) {
                if (el.name && el.name !== 'gateway' && el.name !== 'currency' && el.name !== 'amount' && el.name !== 'promoCode' && el.name !== 'agree') {
                    if (el.type === 'checkbox') {
                        data[el.name] = el.checked;
                    } else if (el.type === 'radio') {
                        if (el.checked) data[el.name] = el.value;
                    } else {
                        data[el.name] = el.value;
                    }
                }
            });
        }

        return data;
    }

    // ── Form submit ────────────────────────────────────────
    function onSubmit(e) {
        e.preventDefault();
        if (submitBtn.disabled) return;

        setLoading(true);

        fetch(resolveUrl('api/lk/purchase'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(collectFormData())
        })
        .then(parseJsonResponse)
        .then(function (res) {
            if (res.ok && res.data.redirect) {
                if (typeof notyf !== 'undefined' && res.data.message) {
                    notyf.open({ type: 'success', message: res.data.message });
                }
                window.location.href = res.data.redirect;
            } else {
                setLoading(false);
                var msg = res.data.error || 'Error';
                if (typeof notyf !== 'undefined') {
                    notyf.open({ type: 'error', message: msg });
                }
            }
        })
        .catch(function (error) {
            setLoading(false);
            if (typeof notyf !== 'undefined') {
                notyf.open({ type: 'error', message: error && error.message ? error.message : 'Network error' });
            }
        });
    }

    function setLoading(on) {
        if (!submitBtn) return;
        submitBtn.disabled = on;
        submitBtn.classList.toggle('is-loading', on);
        var text = submitBtn.querySelector('[data-lk-btn-text]');
        var loader = submitBtn.querySelector('.lk-submit__loader');
        if (text) text.style.opacity = on ? '0' : '1';
        if (loader) {
            loader.style.display = on ? '' : 'none';
        }
    }

    // ── Events ─────────────────────────────────────────────
    // Currency radios
    root.querySelectorAll('input[name="currency"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            onCurrencyChange(this.value);
        });
    });

    // Gateway radios (event delegation)
    root.addEventListener('change', function (e) {
        if (e.target.matches('input[name="gateway"]') && !e.target.disabled) {
            onGatewayChange(e.target.value);
        }
    });

    // Amount input
    if (amountInput) {
        var onAmountInput = debounce(function () {
            var raw = amountInput.value.replace(/[\s\u00A0]/g, '').replace(',', '.');
            var val = parseFloat(raw) || 0;
            state.amount = val;
            renderPresets();
            recalculate();
        }, 300);

        amountInput.addEventListener('input', onAmountInput);
    }

    // Promo input
    if (promoInput) {
        promoInput.addEventListener('input', validatePromoDebounced);
    }

    // Agree checkbox
    if (agreeCheckbox) {
        agreeCheckbox.addEventListener('change', function () {
            state.agree = this.checked;
            updateSubmitState();
        });
    }

    // Form submit
    if (form) {
        form.addEventListener('submit', onSubmit);
    }

    // ── Init ───────────────────────────────────────────────
    function init() {
        // Determine initial currency
        var checkedCur = root.querySelector('input[name="currency"]:checked');
        var hiddenCur = root.querySelector('input[name="currency"][type="hidden"]');
        state.currency = checkedCur ? checkedCur.value : (hiddenCur ? hiddenCur.value : (cfg.currencies[0] || ''));

        // Determine initial gateway
        var activeGroup = root.querySelector('[data-lk-gateways="' + state.currency + '"]');
        if (activeGroup) {
            var checkedGw = activeGroup.querySelector('input[type="radio"]:checked');
            if (checkedGw) {
                state.gateway = checkedGw.value;
            }
        }
        var hiddenGw = root.querySelector('[data-lk-gateway-hidden="' + state.currency + '"]');
        if (hiddenGw && !state.gateway) {
            state.gateway = hiddenGw.value;
        }

        // Read actual checkbox state from DOM (handles pre-checked state)
        if (agreeCheckbox) {
            state.agree = agreeCheckbox.checked;
        }

        // Disable inputs in hidden gateway field containers
        updateGatewayFields(state.gateway);

        updateHint();
        renderPresets();
        updateSubmitState();
    }

    init();
})();
