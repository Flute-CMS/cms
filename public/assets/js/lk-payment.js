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
    var promoBtn = root.querySelector('[data-lk-promo-btn]');
    var promoMsgEl = root.querySelector('[data-lk-promo-msg]');
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

    // ── Templates ────────────────────────────────────────────
    function tpl(name) {
        var t = root.querySelector('[data-lk-tpl="' + name + '"]');
        return t ? t.content.cloneNode(true).firstElementChild : null;
    }

    function appendReceiptRow(type, label, value) {
        var row = tpl(type);
        if (!row) {
            row = document.createElement('div');
            row.className = type === 'receipt-total' ? 'lk-receipt__total'
                : type === 'receipt-row-green' ? 'lk-receipt__row lk-receipt__row--green'
                : 'lk-receipt__row';
            var s1 = document.createElement('span');
            var s2 = document.createElement('span');
            row.appendChild(s1);
            row.appendChild(s2);
        }
        var labelEl = row.querySelector('[data-label]') || row.children[0];
        var valueEl = row.querySelector('[data-value]') || row.children[1];
        if (labelEl) labelEl.textContent = label;
        if (valueEl) valueEl.textContent = value;
        receiptEl.appendChild(row);
    }

    // ── Presets ────────────────────────────────────────────
    function getPresets(cur) {
        return cfg.presets[cur] || [];
    }

    function renderPresets() {
        if (!presetsWrap) return;
        var presets = getPresets(state.currency);
        if (!presets.length) {
            presetsWrap.innerHTML = '';
            return;
        }
        // Re-use existing SSR buttons if they match, otherwise rebuild
        var existing = presetsWrap.querySelectorAll('.lk-preset');
        var needsRebuild = existing.length !== presets.length;
        if (!needsRebuild) {
            for (var i = 0; i < presets.length; i++) {
                if (Number(existing[i].getAttribute('data-amount')) !== presets[i]) {
                    needsRebuild = true;
                    break;
                }
            }
        }
        if (needsRebuild) {
            presetsWrap.innerHTML = '';
            presets.forEach(function (val) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'lk-preset';
                btn.setAttribute('data-amount', val);
                btn.textContent = val.toLocaleString();
                presetsWrap.appendChild(btn);
            });
        }
        // Update active state and ensure listeners
        presetsWrap.querySelectorAll('.lk-preset').forEach(function (btn) {
            var val = Number(btn.getAttribute('data-amount'));
            btn.classList.toggle('is-active', state.amount === val);
        });
    }

    // Event delegation for preset clicks (works for both SSR and JS-rendered buttons)
    if (presetsWrap) {
        presetsWrap.addEventListener('click', function (e) {
            var btn = e.target.closest('.lk-preset');
            if (!btn) return;
            var val = Number(btn.getAttribute('data-amount'));
            state.amount = val;
            amountInput.value = formatAmountDisplay(String(val));
            renderPresets();
            recalculate();
        });
    }

    // ── Hint ───────────────────────────────────────────────
    function updateHint() {
        if (!hintEl) return;
        var min = getEffectiveMin();
        if (min > 0) {
            var tpl = cfg.i18n.min_amount_info || '';
            hintEl.textContent = tpl.replace(/:amount/g, min).replace(/:currency/g, state.currency);
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

        // Promo — all types are bonuses to balance
        if (state.promoValid && state.promoDetails) {
            var p = state.promoDetails;
            if (p.type === 'amount') {
                amountToReceive += p.value;
            } else if (p.type === 'percentage') {
                amountToReceive += Math.round((amountToReceive * p.value) / 100 * 100) / 100;
            }
        }

        var feeAmount = fee > 0 ? Math.round((amountToPay * fee) / 100 * 100) / 100 : 0;
        var totalToPay = amountToPay + feeAmount;

        // Build receipt via templates
        receiptEl.innerHTML = '';

        appendReceiptRow('receipt-row', cfg.i18n.base_amount, formatNumber(amount) + ' ' + state.currency);
        appendReceiptRow('receipt-row', cfg.i18n.select_gateway, gwData ? gwData.name : '—');

        if (bonus > 0) {
            appendReceiptRow('receipt-row-green', cfg.i18n.gateway_bonus + ' (+' + bonus + '%)', '+' + formatNumber(bonusAmount, 0) + ' ' + cfg.currencyView);
        }

        if (state.promoValid && state.promoDetails) {
            var p = state.promoDetails;
            if (p.type === 'amount') {
                appendReceiptRow('receipt-row-green', cfg.i18n.bonus, '+' + p.value + ' ' + cfg.currencyView);
            } else if (p.type === 'percentage') {
                appendReceiptRow('receipt-row-green', cfg.i18n.bonus, '+' + p.value + '%');
            }
        }

        if (fee > 0) {
            appendReceiptRow('receipt-row', cfg.i18n.gateway_fee, '+' + formatNumber(feeAmount) + ' ' + state.currency);
        }

        appendReceiptRow('receipt-row-green', cfg.i18n.you_will_receive, formatNumber(amountToReceive) + ' ' + cfg.currencyView);
        appendReceiptRow('receipt-total', cfg.i18n.to_pay, formatNumber(totalToPay) + ' ' + state.currency);
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
    function validatePromo() {
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
                var successMsg = payload.type === 'percentage'
                    ? (cfg.i18n.promo_applied_bonus || 'Бонус') + ' +' + payload.value + '%'
                    : (cfg.i18n.promo_applied_bonus || 'Бонус') + ' +' + payload.value;
                setPromoState('valid', successMsg);
            } else {
                state.promoValid = false;
                state.promoDetails = null;
                setPromoState('invalid', payload.message || cfg.i18n.promo_invalid || 'Промокод недействителен');
            }
            recalculate();
        })
        .catch(function () {
            state.promoValid = false;
            state.promoDetails = null;
            setPromoState('');
            recalculate();
        });
    }

    function clearPromo() {
        if (promoInput) promoInput.value = '';
        state.promoCode = '';
        state.promoValid = false;
        state.promoDetails = null;
        setPromoState('');
        updatePromoBtn();
        recalculate();
    }

    function updatePromoBtn() {
        if (!promoBtn) return;
        var code = (promoInput ? promoInput.value : '').trim();
        if (code || state.promoValid) {
            show(promoBtn);
            promoBtn.classList.toggle('is-applied', state.promoValid);
            promoBtn.textContent = state.promoValid
                ? (promoBtn.getAttribute('data-label-clear') || 'Clear')
                : (promoBtn.getAttribute('data-label-apply') || 'Apply');
        } else {
            hide(promoBtn);
        }
    }

    function setPromoState(s, message) {
        if (!promoWrap) return;

        var fieldEl = promoWrap.querySelector('.lk-promo__field');
        if (fieldEl) {
            fieldEl.classList.toggle('is-valid', s === 'valid');
            fieldEl.classList.toggle('has-error', s === 'invalid');
        }

        if (promoMsgEl) {
            promoMsgEl.className = 'lk-promo__message';
            if (s && message) {
                promoMsgEl.classList.add('lk-promo__message--' + s);
                promoMsgEl.textContent = message;
            } else {
                promoMsgEl.textContent = '';
            }
        }

        updatePromoBtn();
    }

    // ── Agree checkbox ──────────────────────────────────────
    if (agreeCheckbox) {
        agreeCheckbox.addEventListener('change', function () {
            state.agree = this.checked;
            updateSubmitState();
        });
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

        // Optimistic: show redirect overlay immediately
        showRedirectOverlay();

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
                window.location.href = res.data.redirect;
            } else {
                hideRedirectOverlay();
                var msg = res.data.error || 'Error';
                if (typeof notyf !== 'undefined') {
                    notyf.open({ type: 'error', message: msg });
                }
            }
        })
        .catch(function (error) {
            hideRedirectOverlay();
            if (typeof notyf !== 'undefined') {
                notyf.open({ type: 'error', message: error && error.message ? error.message : 'Network error' });
            }
        });
    }

    // ── Redirect overlay ────────────────────────────────────
    var redirectOverlay = null;

    // Yoyo wraps the component — we need to remove it from DOM to stop re-renders
    var yoyoWrap = root.closest('[id^="yoyo"]') || root.parentNode;
    var yoyoParent = null;
    var yoyoAnchor = null;

    function showRedirectOverlay() {
        var t = root.querySelector('[data-lk-tpl="redirect"]');
        if (t) {
            redirectOverlay = t.content.cloneNode(true).firstElementChild;
        } else {
            redirectOverlay = document.createElement('div');
            redirectOverlay.className = 'lk-redirect-overlay';
            redirectOverlay.innerHTML =
                '<div class="lk-redirect-overlay__spinner"></div>' +
                '<p class="lk-redirect-overlay__text">' + (cfg.i18n.redirecting || 'Redirecting…') + '</p>';
        }

        // Remember position, remove Yoyo element entirely so HTMX can't swap it
        yoyoParent = yoyoWrap.parentNode;
        yoyoAnchor = yoyoWrap.nextSibling;
        yoyoParent.insertBefore(redirectOverlay, yoyoWrap);
        yoyoParent.removeChild(yoyoWrap);
    }

    function hideRedirectOverlay() {
        // Restore Yoyo element
        if (yoyoParent && yoyoWrap) {
            if (yoyoAnchor) {
                yoyoParent.insertBefore(yoyoWrap, yoyoAnchor);
            } else {
                yoyoParent.appendChild(yoyoWrap);
            }
        }
        if (redirectOverlay && redirectOverlay.parentNode) {
            redirectOverlay.parentNode.removeChild(redirectOverlay);
            redirectOverlay = null;
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
    function formatAmountDisplay(str) {
        var clean = str.replace(/[\s\u00A0]/g, '').replace(',', '.');
        var parts = clean.split('.');
        var intPart = parts[0].replace(/\D/g, '');
        var formatted = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        if (parts.length > 1) {
            formatted += '.' + parts[1].replace(/[^\d]/g, '');
        }
        return formatted;
    }

    if (amountInput) {
        amountInput.addEventListener('input', function () {
            var pos = amountInput.selectionStart;
            var before = amountInput.value;
            var spacesBefore = (before.slice(0, pos).match(/\s/g) || []).length;

            var formatted = formatAmountDisplay(before);
            amountInput.value = formatted;

            var spacesAfter = (formatted.slice(0, pos).match(/\s/g) || []).length;
            var newPos = pos + (spacesAfter - spacesBefore);
            amountInput.setSelectionRange(newPos, newPos);
        });

        var onAmountCalc = debounce(function () {
            var raw = amountInput.value.replace(/[\s\u00A0]/g, '').replace(',', '.');
            var val = parseFloat(raw) || 0;
            state.amount = val;
            renderPresets();
            recalculate();
        }, 300);

        amountInput.addEventListener('input', onAmountCalc);
    }

    // Promo input — show/hide button on typing, reset state when cleared
    if (promoInput) {
        promoInput.addEventListener('input', function () {
            var code = promoInput.value.trim();
            if (!code && (state.promoValid || state.promoCode)) {
                state.promoCode = '';
                state.promoValid = false;
                state.promoDetails = null;
                setPromoState('');
                recalculate();
            }
            updatePromoBtn();
        });
        promoInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (state.promoValid) {
                    clearPromo();
                } else {
                    validatePromo();
                }
            }
        });
    }

    // Promo apply/clear button
    if (promoBtn) {
        promoBtn.addEventListener('click', function () {
            if (state.promoValid) {
                clearPromo();
            } else {
                validatePromo();
            }
        });
    }

    // Form submit
    if (form) {
        form.addEventListener('submit', onSubmit);
    }

    // ── Step mode integration ────────────────────────────────
    // Validation is handled by steps.js via data-steps-validate on each panel.
    // We only register global validator functions and the recalc listener.
    if (cfg.stepMode) {
        // Step 1 validator: require valid amount
        window.validateLkAmount = function () {
            var raw = amountInput ? amountInput.value.replace(/[\s\u00A0]/g, '').replace(',', '.') : '';
            var val = parseFloat(raw) || 0;
            var min = getEffectiveMin();
            if (val <= 0 || (min > 0 && val < min)) {
                if (amountInput) {
                    amountInput.focus();
                    amountInput.classList.add('is-error');
                    setTimeout(function () { amountInput.classList.remove('is-error'); }, 1500);
                }
                return false;
            }
            return true;
        };

        // Step 2 validator: require gateway selected
        window.validateLkMethod = function () {
            return !!state.gateway;
        };

        // Recalculate receipt when entering confirmation step
        root.addEventListener('steps:change', function (e) {
            var detail = e.detail || {};
            if (detail.stepName === 'lk-confirm') {
                recalculate();
            }
        });
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

        // Read actual checkbox state from DOM
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
