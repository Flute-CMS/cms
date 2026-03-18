$(document).ready(function () {
	initDatePickers();

	function getSystemLocale() {
		// 1. Read from <html lang="...">
		var htmlLang = document.documentElement.getAttribute("lang");
		if (htmlLang) {
			// Normalize: "uk" -> "uk", "pt-BR" -> "pt", "zh-CN" -> "zh"
			var code = htmlLang.toLowerCase().split("-")[0];
			if (typeof flatpickr !== "undefined" && flatpickr.l10ns && flatpickr.l10ns[code]) {
				return flatpickr.l10ns[code];
			}
		}
		return undefined;
	}

	function initDatePickers(root) {
		var scope = root instanceof Element ? root : document;
		var pickers = scope.querySelectorAll("[data-datepicker]");

		pickers.forEach(function (wrapper) {
			if (wrapper._fpInit) return;
			if (!wrapper.isConnected) return;

			var input = wrapper.querySelector(".datepicker-field__input");
			if (!input || !input.isConnected) return;

			if (typeof flatpickr === "undefined") {
				setTimeout(function () { initDatePickers(root); }, 100);
				return;
			}

			wrapper._fpInit = true;

			var config = {};
			try {
				config = JSON.parse(wrapper.dataset.datepickerConfig || "{}");
			} catch (e) {
				console.error("DatePicker: invalid config JSON", e);
			}

			// Resolve locale: explicit config > html lang > default
			if (config.locale && typeof flatpickr.l10ns !== "undefined" && flatpickr.l10ns[config.locale]) {
				config.locale = flatpickr.l10ns[config.locale];
			} else {
				var sysLocale = getSystemLocale();
				if (sysLocale) {
					config.locale = sysLocale;
				} else {
					delete config.locale;
				}
			}

			var clearBtn = wrapper.querySelector(".datepicker-field__clear");

			function toggleClear(dateStr) {
				if (!clearBtn) return;
				clearBtn.style.display = dateStr ? "" : "none";
			}

			var options = Object.assign(
				{
					disableMobile: true,
					static: true,
					appendTo: wrapper,
					prevArrow:
						'<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
					nextArrow:
						'<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
					onChange: function (selectedDates, dateStr) {
						input.dispatchEvent(new Event("input", { bubbles: true }));
						input.dispatchEvent(new Event("change", { bubbles: true }));
						toggleClear(dateStr);
					},
					onReady: function (selectedDates, dateStr) {
						toggleClear(dateStr);
					},
				},
				config
			);

			var fp = flatpickr(input, options);
			wrapper._fp = fp;

			if (clearBtn) {
				clearBtn.addEventListener("click", function (e) {
					e.preventDefault();
					e.stopPropagation();
					fp.clear();
					input.dispatchEvent(new Event("input", { bubbles: true }));
					input.dispatchEvent(new Event("change", { bubbles: true }));
				});
			}

			var iconEl = wrapper.querySelector(".datepicker-field__icon");
			if (iconEl) {
				iconEl.style.cursor = "pointer";
				iconEl.addEventListener("click", function () {
					if (!input.disabled) fp.open();
				});
			}
		});
	}

	window.initDatePickers = function (root) {
		try {
			initDatePickers(root || document);
		} catch (e) {
			console.error("initDatePickers error:", e);
		}
	};

	// HTMX: destroy BEFORE swap so the old DOM gets cleaned up
	document.body.addEventListener("htmx:beforeSwap", function (evt) {
		var el = evt.target;
		if (!el) return;
		destroyDatePickers(el);
	});

	// HTMX: re-init AFTER new content settles
	document.body.addEventListener("htmx:afterSettle", function (evt) {
		initDatePickers(evt.detail.target);
	});

	// Also handle element-level cleanup (Yoyo morph)
	document.body.addEventListener("htmx:beforeCleanupElement", function (evt) {
		var el = evt.target;
		if (!el) return;
		destroyDatePickers(el);
	});

	function destroyDatePickers(el) {
		var candidates = [];
		if (el.matches && el.matches("[data-datepicker]")) candidates.push(el);
		if (el.querySelectorAll) {
			candidates.push.apply(candidates, el.querySelectorAll("[data-datepicker]"));
		}
		candidates.forEach(function (wrapper) {
			if (wrapper._fp) {
				try { wrapper._fp.destroy(); } catch (_) {}
				wrapper._fp = null;
			}
			wrapper._fpInit = false;
		});
	}
});
