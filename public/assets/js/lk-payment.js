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

    var sectionAmount = root.querySelector('[data-lk-section="amount"]');
    var sectionAmountDiv = root.querySelector('[data-lk-section="amount-divider"]');
    var sectionCheckout = root.querySelector('[data-lk-section="checkout"]');
    var sectionCheckoutDiv = root.querySelector('[data-lk-section="checkout-divider"]');

    // ── Helpers ────────────────────────────────────────────
    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
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
            btn.className = 'lk-amount__preset' + (state.amount === val ? ' is-active' : '');
            btn.setAttribute('data-amount', val);
            btn.textContent = val.toLocaleString();
            btn.addEventListener('click', function () {
                state.amount = val;
                amountInput.value = val;
                renderPresets();
                recalculate();
                revealCheckout();
            });
            presetsWrap.appendChild(btn);
        });
    }

    // ── Hint ───────────────────────────────────────────────
    function updateHint() {
        if (!hintEl) return;
        var min = getEffectiveMin();
        var tpl = cfg.i18n.min_amount_info || '';
        hintEl.textContent = tpl.replace(':amount', min).replace(':currency', state.currency);
    }

    function getEffectiveMin() {
        // Gateway-specific min
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
                // Enable radios
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

        updateHint();
        renderPresets();
        revealAmount();
        hideCheckout();
        recalculate();
    }

    // ── Gateway switching ──────────────────────────────────
    function onGatewayChange(gw) {
        state.gateway = gw;
        updateHint();
        recalculate();
        revealAmount();
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
        var feeAmount = fee > 0 ? Math.round((amount * fee) / 100 * 100) / 100 : 0;
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

        // Build receipt HTML
        var html = '';
        html += '<div class="lk-receipt__row">';
        html += '<span>' + cfg.i18n.base_amount + '</span>';
        html += '<span>' + formatNumber(amount) + ' ' + state.currency + '</span>';
        html += '</div>';

        if (fee > 0) {
            html += '<div class="lk-receipt__row lk-receipt__row--dim">';
            html += '<span>' + cfg.i18n.gateway_fee + '</span>';
            html += '<span>' + fee + '% (~' + formatNumber(feeAmount, 0) + ' ' + state.currency + ')</span>';
            html += '</div>';
        }

        if (bonus > 0) {
            html += '<div class="lk-receipt__row lk-receipt__row--green">';
            html += '<span>' + cfg.i18n.gateway_bonus + ' (+' + bonus + '%)</span>';
            html += '<span>+' + formatNumber(bonusAmount, 0) + ' ' + cfg.currencyView + '</span>';
            html += '</div>';
        }

        html += promoHtml;

        html += '<div class="lk-receipt__total">';
        html += '<span>' + cfg.i18n.you_will_receive + '</span>';
        html += '<span>' + formatNumber(amountToReceive) + ' ' + cfg.currencyView + '</span>';
        html += '</div>';

        receiptEl.innerHTML = html;
        show(receiptEl);
        updateSubmitState();
    }

    // ── Section reveal ─────────────────────────────────────
    function revealAmount() {
        if (state.gateway) {
            show(sectionAmountDiv);
            show(sectionAmount);
        } else {
            hide(sectionAmountDiv);
            hide(sectionAmount);
        }
    }

    function revealCheckout() {
        if (state.amount > 0 && state.gateway) {
            show(sectionCheckoutDiv);
            show(sectionCheckout);
        }
    }

    function hideCheckout() {
        hide(sectionCheckoutDiv);
        hide(sectionCheckout);
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

        fetch(u('api/lk/validate-promo'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ promoCode: code, amount: state.amount })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.valid) {
                state.promoValid = true;
                state.promoDetails = { type: data.type, value: data.value };
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

    // ── Form submit ────────────────────────────────────────
    function onSubmit(e) {
        e.preventDefault();
        if (submitBtn.disabled) return;

        setLoading(true);

        fetch(u('api/lk/purchase'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                gateway: state.gateway,
                currency: state.currency,
                amount: state.amount,
                promoCode: state.promoCode,
                agree: state.agree
            })
        })
        .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
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
        .catch(function () {
            setLoading(false);
            if (typeof notyf !== 'undefined') {
                notyf.open({ type: 'error', message: 'Network error' });
            }
        });
    }

    function setLoading(on) {
        if (!submitBtn) return;
        submitBtn.disabled = on;
        submitBtn.classList.toggle('is-loading', on);
        var text = submitBtn.querySelector('[data-lk-btn-text]');
        var loader = submitBtn.querySelector('.lk-pay-btn__loader');
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
            if (val > 0) revealCheckout();
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
        // Also check hidden gateway
        var hiddenGw = root.querySelector('[data-lk-gateway-hidden="' + state.currency + '"]');
        if (hiddenGw && !state.gateway) {
            state.gateway = hiddenGw.value;
        }

        updateHint();
        renderPresets();
        revealAmount();
        updateSubmitState();
    }

    init();
})();
