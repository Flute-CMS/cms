/**
 * Flute App.js - Main frontend functionality
 *
 * Однажды мудрец сказал:
 * "Если ты в душе не ебешь как это работает, то не трогай"
 */
class FluteApp {
	constructor() {
		this.notyf = new Notyf({
			duration: 4000,
			position: { x: "right", y: "top" },
			dismissible: true,
			ripple: false,
		});

		this.notifications = new NotificationManager(this.notyf);
		this.modals = new ModalManager();
		this.tooltips = new TooltipManager();
		this.dropdowns = new DropdownManager();
		this.theme = new ThemeManager();
		this.forms = new FormManager();
		this.confirmations = new ConfirmationManager();

		this.nprogressTimeout = null;
		this.authToken = null;
		this.authTokenInitialized = false;
		this.authCheckInterval = null;

		this.initEvents();
		this.initAuthCheck();
	}

	initEvents() {
		window.addEventListener("DOMContentLoaded", () => {
			this.notifications.updateNotificationCount();
		});

		this.setupHtmxEvents();

		$(document).ready(() => {
			this.forms.initInputHandlers();
		});
	}

	initAuthCheck() {
		this.authToken = document
			.querySelector('meta[name="auth-token"]')
			?.getAttribute("content");

		if (this.authToken) {
			this.authCheckInterval = setInterval(
				() => this.checkAuthStatus(),
				10000
			);

			window.addEventListener("htmx:afterRequest", (evt) => {
				this.checkAuthToken(evt.detail.xhr);
			});
		}
	}

	checkAuthStatus() {
		fetch(u("api/auth/check"), {
			method: "HEAD",
			headers: {
				"X-Requested-With": "XMLHttpRequest",
			},
		})
			.then((response) => {
				this.checkAuthToken(response);
			})
			.catch((error) => {
				console.error("Ошибка проверки статуса авторизации:", error);
			});
	}

	checkAuthToken(response) {
		if (!response.headers) return;

		const newAuthToken = response.headers.get("Auth-Token");
		const isLoggedIn = response.headers.get("Is-Logged-In");

		if (!newAuthToken) return;

		if (!this.authTokenInitialized) {
			this.authToken = newAuthToken;
			this.authTokenInitialized = true;
			return;
		}

		if (this.authToken !== newAuthToken) {
			this.authToken = newAuthToken;

			const verify = () =>
				fetch(u("api/auth/check"), {
					method: "HEAD",
					headers: { "X-Requested-With": "XMLHttpRequest" },
				})
					.then((r) => r.headers.get("Is-Logged-In"))
					.then((state) => {
						if (state === "false") {
							window.location.reload();
						} else {
							window.location.reload();
						}
					})
					.catch(() => window.location.reload());

			if (isLoggedIn === "false") {
				setTimeout(verify, 1000);
			} else {
				window.location.reload();
			}
			return;
		}
	}

	setupHtmxEvents() {
		window.addEventListener("htmx:sendError", (evt) => {
			const lang = document.querySelector("html").getAttribute("lang");
			const message =
				lang === "ru"
					? "Произошла ошибка при выполнении запроса. Пожалуйста, перезагрузите страницу."
					: "Error sending request. Please refresh the page and try again.";

			this.notyf.open({ type: "error", message });
		});

		// Handle status codes
		htmx.on("htmx:beforeSwap", (evt) => {
			const status = evt.detail.xhr.status;
			if ([400, 403, 404, 422, 500, 503].includes(status)) {
				const elt = evt.detail.elt;
				const swapAttr =
					elt && typeof elt.getAttribute === "function"
						? elt.getAttribute("hx-swap")
						: null;
				const isSwapNone = (swapAttr || "").toLowerCase() === "none";
				const url =
					(evt.detail &&
						evt.detail.pathInfo &&
						evt.detail.pathInfo.requestPath) ||
					(evt.detail &&
						evt.detail.xhr &&
						evt.detail.xhr.responseURL) ||
					"";
				const isApi =
					typeof url === "string" && url.indexOf("/api/") !== -1;

				if (isApi || isSwapNone) {
					evt.detail.shouldSwap = false;
					evt.detail.isError = false;
					return;
				}

				evt.detail.shouldSwap = true;
				evt.detail.isError = false;
			}
		});

		// Scroll to top and handle navbar on page change
		htmx.on("htmx:afterSwap", (event) => {
			if (event.detail.target.tagName.toLowerCase() === "main") {
				setTimeout(() => {
					window.scrollTo({
						top: 0,
						behavior: "smooth",
					});
				}, 20);

				const navbarItems = document.querySelectorAll(
					".navbar__items-item"
				);
				navbarItems.forEach((item) => item.classList.remove("active"));

				const currentPath = event.detail.pathInfo.requestPath || "/";

				let bestMatch = null;
				let bestMatchLength = -1;

				navbarItems.forEach((item) => {
					const href = item.getAttribute("href");
					if (!href) return;

					const itemPath = new URL(href, window.location.origin)
						.pathname;

					if (itemPath === "/" && currentPath !== "/") {
						return;
					}

					if (
						currentPath.startsWith(itemPath) &&
						itemPath.length > bestMatchLength
					) {
						bestMatch = item;
						bestMatchLength = itemPath.length;
					}
				});

				if (bestMatch) {
					bestMatch.classList.add("active");
				}
			}
		});

		htmx.on("htmx:historyRestore", () => {
			window.scrollTo({
				top: 0,
				behavior: "smooth",
			});

			const navbarItems = document.querySelectorAll(
				".navbar__items-item"
			);
			navbarItems.forEach((item) => item.classList.remove("active"));

			const currentPath = new URL(window.location.href).pathname || "/";

			let bestMatch = null;
			let bestMatchLength = -1;

			navbarItems.forEach((item) => {
				const href = item.getAttribute("href");
				if (!href) return;

				const itemPath = new URL(href, window.location.origin).pathname;

				if (itemPath === "/" && currentPath !== "/") {
					return;
				}

				if (
					currentPath.startsWith(itemPath) &&
					itemPath.length > bestMatchLength
				) {
					bestMatch = item;
					bestMatchLength = itemPath.length;
				}
			});

			if (bestMatch) {
				bestMatch.classList.add("active");
			}
		});

		window.addEventListener("htmx:configRequest", (evt) => {
			const csrfToken = document
				.querySelector('meta[name="csrf-token"]')
				?.getAttribute("content");
			if (csrfToken) {
				evt.detail.headers["X-CSRF-Token"] = csrfToken;
			}
		});

		// Main HTMX events
		htmx.on("htmx:afterSettle", () =>
			this.notifications.updateNotificationCount()
		);
		htmx.on("htmx:afterRequest", (evt) =>
			this.notifications.handleToasts(evt)
		);

		// NProgress integration
		htmx.on("htmx:beforeRequest", (e) => this.handleNProgress(e, "start"));
		htmx.on("htmx:afterRequest", (e) => this.handleNProgress(e, "done"));
		htmx.on("htmx:sendError", (e) => this.handleNProgress(e, "done"));
		htmx.on("htmx:historyRestore", NProgress.remove);

		const ensureScrollUnlocked = () => {
			document.body.classList.remove("no-scroll");
		};
		htmx.on("htmx:beforeSwap", ensureScrollUnlocked);
		htmx.on("htmx:afterSwap", ensureScrollUnlocked);
		htmx.on("htmx:afterSettle", ensureScrollUnlocked);
		htmx.on("htmx:historyRestore", ensureScrollUnlocked);

		// Modal duplication prevention
		htmx.on("htmx:beforeSwap", (event) => {
			const incomingHTML = event.detail.xhr.response;
			if (!incomingHTML) return;

			try {
				const parser = new DOMParser();
				const doc = parser.parseFromString(incomingHTML, "text/html");
				const newModals = doc.querySelectorAll(".modal");

				newModals.forEach((newModal) => {
					const id = newModal.id;
					if (!id) return;

					const existingModals = document.querySelectorAll(
						`#modals > .modal#${id}`
					);
					existingModals.forEach((existingModal) => {
						existingModal.remove();
					});
				});
			} catch (error) {
				console.warn("Error handling modals in HTMX swap:", error);
			}
		});

		// Fix for HTMX history caching
		// htmx.on('htmx:pushedIntoHistory', () => {
		//     localStorage.removeItem('htmx-history-cache');
		// });

		htmx.onLoad((content) => {
			$(".clear-input").hide();
		});

		// Prevent error partials from altering document title
		htmx.on("htmx:afterSwap", (evt) => {
			try {
				const status = evt.detail && evt.detail.xhr ? evt.detail.xhr.status : 200;
				if (status >= 400) {
					const originalTitle = document.title;
					const titles = document.head.querySelectorAll('title');
					if (titles.length > 1) {
						for (let i = 1; i < titles.length; i++) {
							titles[i].parentNode.removeChild(titles[i]);
						}
						document.title = originalTitle;
					}
				}
			} catch (_) {}
		});
	}

	// NProgress handling during HTMX requests
	handleNProgress(event, action) {
		const PROGRESS_DELAY = 150;
		const triggerElement = event.detail.elt;
		const xhr = event.detail.xhr;

		if (
			!triggerElement.hasAttribute("data-noprogress") &&
			xhr.status !== 304
		) {
			if (action === "start") {
				if (!this.nprogressTimeout) {
					this.nprogressTimeout = setTimeout(() => {
						NProgress.start();
						this.nprogressTimeout = null;
					}, PROGRESS_DELAY);
				}
			} else if (action === "done") {
				clearTimeout(this.nprogressTimeout);
				this.nprogressTimeout = null;
				NProgress.done();
			}
		}
	}
}

/**
 * Notification management
 */
class NotificationManager {
	constructor(notyf) {
		this.notyf = notyf;
		this.initCustomEvents();
		this.initAutoMarkRead();
	}

	updateNotificationCount() {
		const notificationCount = document.getElementById("notification-count");
		if (!notificationCount) return;

		const text = notificationCount.textContent.trim();
		if (text === "") return;

		const count = parseInt(text, 10) || 0;
		notificationCount.style.display = count > 0 ? "inline-flex" : "none";
	}

	handleToasts(evt) {
		try {
			const toastsHeader = evt.detail.xhr.getResponseHeader("X-Toasts");
			if (toastsHeader) {
				const toasts = JSON.parse(toastsHeader);
				if (Array.isArray(toasts)) {
					toasts.forEach((toast) => this.displayToast(toast));
				}
			}
		} catch (error) {
			console.error("Error handling toast notifications:", error);
		}
	}

	displayToast(toast) {
		if (!toast) return;

		const options = {};

		// Copy toast properties to options
		if (toast.type) options.type = toast.type;
		if (toast.message) options.message = toast.message;
		if (toast.duration) options.duration = toast.duration;
		if (toast.dismissible) options.dismissible = toast.dismissible;
		if (toast.ripple) options.ripple = toast.ripple;
		if (toast.position) options.position = toast.position;
		if (toast.icon) options.icon = toast.icon;
		if (toast.className) options.className = toast.className;

		// Handle event handlers
		if (toast.events) {
			try {
				const eventHandlers = {};
				Object.entries(toast.events).forEach(([eventName, handler]) => {
					eventHandlers[eventName] = () => {
						new Function(handler)();
					};
				});

				// Add events after creating handlers
				Object.entries(eventHandlers).forEach(
					([eventName, handlerFn]) => {
						this.notyf.on(eventName, handlerFn);
					}
				);
			} catch (error) {
				console.error("Error processing toast event handlers:", error);
			}
		}

		this.notyf.open(options);
	}

	initCustomEvents() {
		window.addEventListener("delayed-redirect", (event) => {
			try {
				const { url, delay } = event.detail;
				if (url && delay) {
					setTimeout(() => {
						window.location.href = url;
					}, delay);
				}
			} catch (error) {
				console.error("Error handling delayed redirect:", error);
			}
		});
	}

	initAutoMarkRead() {
		document.addEventListener("click", (e) => {
			const item = e.target.closest(".notification-item.unread");
			if (!item) return;

			const id = item.getAttribute("data-id");
			if (!id) return;

			item.classList.remove("unread");
			item.classList.add("viewed");
			const dot = item.querySelector(".notification-unread-indicator");
			if (dot) dot.remove();

			fetch(u(`api/notifications/${id}`), {
				method: "PUT",
				headers: {
					"X-Requested-With": "XMLHttpRequest",
					"X-CSRF-Token": document
						.querySelector('meta[name="csrf-token"]')
						.getAttribute("content"),
				},
			}).catch(() => {});

			const badge = document.getElementById("notification-count");
			if (badge) {
				try {
					if (typeof htmx !== "undefined") {
						htmx.trigger(badge, "refresh");
					}
				} catch (_) {}
				setTimeout(() => this.updateNotificationCount(), 120);
			}
		});
	}
}

/**
 * Modal management
 */
class ModalManager {
	constructor() {
		this.initModalEvents();
	}

	initModalEvents() {
		$(document).on("click", "[data-modal-close]", (e) => {
			e.preventDefault();
			const modalAttr = $(e.currentTarget).attr("data-modal-close");
			const modalId = modalAttr
				? modalAttr.replace("#", "")
				: $(e.currentTarget).closest(".modal").attr("id");

			if (modalId) {
				closeModal(modalId);
			}
		});

		$(document).on("click", ".tabbar__modal-item", (e) => {
			closeModal($(e.currentTarget).closest(".modal").attr("id"));
		});

		$(document).on("click", "[data-trigger-right-sidebar]", (e) => {
			e.preventDefault();
			$("#right_sidebar").toggleClass("active");
		});

		$(document).on("click", "#right-sidebar-content a", (e) => {
			const removeHandler = $("#right-sidebar-content").find(
				"[data-remove-handler]"
			);

			if (
				removeHandler.length === 0 &&
				$(e.currentTarget).attr("data-remove-handler") === undefined
			) {
				closeModal("right-sidebar");
			}
		});

		window.addEventListener("open-right-sidebar", () => {
			openModal("right-sidebar");
		});

		window.addEventListener("open-modal", (event) => {
			openModal(event.detail.value || event.detail.modalId);
		});

		window.addEventListener("close-modal", (event) => {
			closeModal(event.detail.value || event.detail.modalId);
		});
	}
}

/**
 * Tooltip management
 */
class TooltipManager {
	constructor() {
		this.tooltipEl = null;
		this.tooltipCleanups = new WeakMap();
		this.activeElement = null;
		this.observer = null;
		this.lastTooltipContent = "";
		this.initTooltipEvents();
		this.initMutationObserver();
	}

	initTooltipEvents() {
		document.body.addEventListener("mouseover", (event) => {
			const target = event.target.closest("[data-tooltip]");
			if (target) this.showTooltip(target);
		});

		document.body.addEventListener("mouseout", (event) => {
			const target = event.target.closest("[data-tooltip]");
			if (target) this.hideTooltip(target);
		});

		window.addEventListener("beforeunload", () => {
			this.cleanup();
		});

		htmx.on("htmx:beforeSwap", () => {
			this.hideAllTooltips();
		});

		document.addEventListener("visibilitychange", () => {
			if (document.visibilityState === "hidden") {
				this.hideAllTooltips();
			}
		});
	}

	initMutationObserver() {
		this.observer = new MutationObserver((mutations) => {
			if (!this.activeElement || !this.tooltipEl) return;

			let elementRemoved = false;
			let contentChanged = false;

			for (const mutation of mutations) {
				// Проверяем, не был ли удален активный элемент
				if (mutation.type === "childList") {
					const removed = Array.from(mutation.removedNodes);
					elementRemoved = removed.some((node) => {
						if (node === this.activeElement) return true;
						if (
							node.nodeType === 1 &&
							node.contains(this.activeElement)
						)
							return true;
						return false;
					});

					if (elementRemoved) {
						this.hideAllTooltips();
						break;
					}

					if (this.activeElement) {
						const tooltipText =
							this.activeElement.getAttribute("data-tooltip");

						// Если tooltip - это селектор
						if (tooltipText && tooltipText.startsWith("#")) {
							try {
								const tooltipContentEl =
									document.querySelector(tooltipText);
								if (tooltipContentEl) {
									for (const mutatedNode of mutation.addedNodes) {
										if (
											tooltipContentEl.contains(
												mutatedNode
											) ||
											mutatedNode.contains(
												tooltipContentEl
											)
										) {
											contentChanged = true;
											break;
										}
									}

									for (const mutatedNode of mutation.removedNodes) {
										if (
											tooltipContentEl.contains(
												mutatedNode
											) ||
											(mutatedNode.nodeType === 1 &&
												mutatedNode.contains(
													tooltipContentEl
												))
										) {
											contentChanged = true;
											break;
										}
									}
								}
							} catch (e) {}
						}
					}
				} else if (mutation.type === "attributes") {
					if (
						mutation.target === this.activeElement &&
						mutation.attributeName === "data-tooltip"
					) {
						contentChanged = true;
					}

					if (
						(this.activeElement === mutation.target ||
							mutation.target.contains(this.activeElement)) &&
						(mutation.attributeName === "style" ||
							mutation.attributeName === "class" ||
							mutation.attributeName === "hidden")
					) {
						const isVisible = this.isElementVisible(
							this.activeElement
						);
						if (!isVisible) {
							this.hideAllTooltips();
							break;
						}
					}
				}
			}

			if (contentChanged && !elementRemoved) {
				this.updateTooltipContent(this.activeElement);
			}
		});

		this.observer.observe(document.body, {
			childList: true,
			subtree: true,
			attributes: true,
			attributeFilter: ["style", "class", "hidden", "data-tooltip"],
			characterData: true,
		});
	}

	updateTooltipContent(element) {
		if (!element || !this.tooltipEl) return;

		const tooltipText = element.getAttribute("data-tooltip");
		let content;

		try {
			const el = document.querySelector(tooltipText);
			content = el ? el.innerHTML : tooltipText;
		} catch {
			content = tooltipText;
		}

		if (content !== this.lastTooltipContent) {
			this.lastTooltipContent = content;
			this.tooltipEl.innerHTML = content;

			this.updateTooltipPosition(element);
		}
	}

	updateTooltipPosition(element) {
		if (!this.tooltipEl || !element) return;

		const tooltipPlacement =
			element.getAttribute("data-tooltip-placement") ?? "top";

		window.FloatingUIDOM.computePosition(element, this.tooltipEl, {
			placement: tooltipPlacement,
			middleware: [
				window.FloatingUIDOM.offset(8),
				window.FloatingUIDOM.flip(),
				window.FloatingUIDOM.shift({ padding: 5 }),
			],
		}).then(({ x, y }) => {
			if (!this.tooltipEl) return;
			Object.assign(this.tooltipEl.style, {
				left: `${x}px`,
				top: `${y}px`,
			});
		});
	}

	isElementVisible(element) {
		if (!element) return false;

		if (!document.body.contains(element)) return false;

		const style = window.getComputedStyle(element);
		if (style.display === "none" || style.visibility === "hidden")
			return false;

		const rect = element.getBoundingClientRect();
		if (rect.width === 0 || rect.height === 0) return false;

		return true;
	}

	showTooltip(element) {
		if (!element) return;

		const tooltipText = element.getAttribute("data-tooltip");
		const tooltipPlacement =
			element.getAttribute("data-tooltip-placement") ?? "top";
		let content;

		try {
			const el = document.querySelector(tooltipText);
			content = el ? el.innerHTML : tooltipText;
		} catch {
			content = tooltipText;
		}

		this.lastTooltipContent = content;

		if (!this.tooltipEl) {
			this.tooltipEl = document.createElement("div");
			this.tooltipEl.className = "tooltip";
			document.body.appendChild(this.tooltipEl);
		}

		this.tooltipEl.innerHTML = content;
		this.tooltipEl.classList.add("show");
		this.activeElement = element;

		const updatePosition = () => {
			if (!this.tooltipEl || !element) return;

			if (!this.isElementVisible(element)) {
				this.hideAllTooltips();
				return;
			}

			window.FloatingUIDOM.computePosition(element, this.tooltipEl, {
				placement: tooltipPlacement,
				middleware: [
					window.FloatingUIDOM.offset(8),
					window.FloatingUIDOM.flip(),
					window.FloatingUIDOM.shift({ padding: 5 }),
				],
			}).then(({ x, y }) => {
				if (!this.tooltipEl) return;
				Object.assign(this.tooltipEl.style, {
					left: `${x}px`,
					top: `${y}px`,
				});
			});
		};

		updatePosition();

		if (this.tooltipCleanups.has(element)) {
			const oldCleanup = this.tooltipCleanups.get(element);
			if (typeof oldCleanup === "function") {
				oldCleanup();
			}
		}

		const cleanup = window.FloatingUIDOM.autoUpdate(
			element,
			this.tooltipEl,
			updatePosition
		);

		this.tooltipCleanups.set(element, cleanup);
	}

	hideTooltip(element) {
		if (this.tooltipEl) {
			this.tooltipEl.classList.remove("show");
		}

		if (this.activeElement === element) {
			this.activeElement = null;
			this.lastTooltipContent = "";
		}

		if (element && this.tooltipCleanups.has(element)) {
			const cleanup = this.tooltipCleanups.get(element);
			if (typeof cleanup === "function") {
				cleanup();
			}
			this.tooltipCleanups.delete(element);
		}
	}

	hideAllTooltips() {
		if (this.tooltipEl) {
			this.tooltipEl.classList.remove("show");
		}

		if (this.activeElement) {
			this.hideTooltip(this.activeElement);
			this.activeElement = null;
			this.lastTooltipContent = "";
		}
	}

	cleanup() {
		if (this.tooltipEl) {
			this.tooltipEl.remove();
			this.tooltipEl = null;
		}

		if (this.observer) {
			this.observer.disconnect();
		}

		this.activeElement = null;
		this.lastTooltipContent = "";
	}
}

/**
 * Dropdown menu management (hover)
 */
class DropdownManager {
	constructor() {
		this.initDropdownEvents();
	}
	initDropdownEvents() {
		$(document).on("click", "[data-dropdown-open]", (event) => {
			event.preventDefault();
			event.stopPropagation();

			const $toggle = $(event.currentTarget);
			const isHoverDropdown =
				$toggle.attr("data-dropdown-hover") === "true";

			if (!isHoverDropdown) {
				this.toggleDropdown($toggle);
			}
		});

		$(document).on(
			"mouseenter",
			'[data-dropdown-open][data-dropdown-hover="true"]',
			(event) => {
				const $toggle = $(event.currentTarget);
				const dropdownName = $toggle.data("dropdown-open");
				const $menu = $(`[data-dropdown="${dropdownName}"]`);

				if ($menu.data("closeTimeout")) {
					clearTimeout($menu.data("closeTimeout"));
					$menu.removeData("closeTimeout");
				}

				if ($menu.hasClass("active")) return;

				if ($toggle.data("openTimeout")) return;

				const openTimeout = setTimeout(() => {
					const toggleHovered = $toggle[0]?.matches(":hover");
					const menuHovered = $menu[0]?.matches(":hover");
					if (toggleHovered || menuHovered) {
						this.openDropdown($toggle, $menu);
					}
					$toggle.removeData("openTimeout");
				}, 200);

				$toggle.data("openTimeout", openTimeout);
			}
		);

		$(document).on(
			"mouseleave",
			'[data-dropdown-open][data-dropdown-hover="true"]',
			(event) => {
				const $toggle = $(event.currentTarget);
				const dropdownName = $toggle.data("dropdown-open");
				const $menu = $(`[data-dropdown="${dropdownName}"]`);

				if ($toggle.data("openTimeout")) {
					clearTimeout($toggle.data("openTimeout"));
					$toggle.removeData("openTimeout");
				}

				const closeTimeout = setTimeout(() => {
					const toggleHovered = $toggle[0]?.matches(":hover");
					const menuHovered = $menu[0]?.matches(":hover");
					if (!toggleHovered && !menuHovered) {
						this.closeDropdown($menu);
					}
				}, 150);

				$menu.data("closeTimeout", closeTimeout);
			}
		);

		$(document).on("mouseenter", "[data-dropdown]", (event) => {
			const $menu = $(event.currentTarget);
			const dropdownName = $menu.data("dropdown");
			const $toggle = $(`[data-dropdown-open="${dropdownName}"]`);
			if ($toggle.attr("data-dropdown-hover") !== "true") return;
			if ($menu.data("closeTimeout")) {
				clearTimeout($menu.data("closeTimeout"));
				$menu.removeData("closeTimeout");
			}
		});

		$(document).on("mouseleave", "[data-dropdown]", (event) => {
			const $menu = $(event.currentTarget);
			const dropdownName = $menu.data("dropdown");
			const $toggle = $(
				`[data-dropdown-open="${dropdownName}"][data-dropdown-hover="true"]`
			);
			if ($toggle.length === 0) return;
			const closeTimeout = setTimeout(() => {
				const toggleHovered = $toggle[0]?.matches(":hover");
				const menuHovered = $menu[0]?.matches(":hover");
				if (!toggleHovered && !menuHovered) {
					this.closeDropdown($menu);
				}
			}, 150);
			$menu.data("closeTimeout", closeTimeout);
		});

		$(document).on("click", (event) => {
			const $target = $(event.target);

			if (
				$target.closest("[data-dropdown-open]").length ||
				$target.closest("[data-dropdown]").length
			) {
				return;
			}

			$("[data-dropdown].active").each((_, element) => {
				const $menu = $(element);
				const dropdownName = $menu.data("dropdown");
				const $toggle = $(`[data-dropdown-open="${dropdownName}"]`);
				const isHoverDropdown =
					$toggle.attr("data-dropdown-hover") === "true";

				if (!isHoverDropdown) {
					this.closeDropdown($menu);
				}
			});
		});

		$(document).on(
			"click",
			"[data-dropdown] a, [data-dropdown] [data-handler]",
			(event) => {
				const $menu = $(event.currentTarget).closest("[data-dropdown]");
				const dropdownName = $menu.data("dropdown");
				const $toggle = $(`[data-dropdown-open="${dropdownName}"]`);
				const isHoverDropdown =
					$toggle.attr("data-dropdown-hover") === "true";

				if (!isHoverDropdown) {
					this.closeDropdown($menu);
				}
			}
		);

		window.addEventListener("beforeunload", () => {
			this.closeAllDropdowns();
		});
	}

	toggleDropdown($toggle) {
		if (!$toggle || !$toggle.length) return;

		const dropdownName = $toggle.data("dropdown-open");
		const $menu = $(`[data-dropdown="${dropdownName}"]`);

		if (!$menu.length) return;

		$("[data-dropdown]")
			.not($menu)
			.each((_, element) => {
				const $otherMenu = $(element);
				if ($otherMenu.hasClass("active")) {
					$otherMenu.removeClass("active");
					const cleanup = $otherMenu.data("autoUpdateCleanup");
					if (cleanup && typeof cleanup === "function") {
						cleanup();
						$otherMenu.removeData("autoUpdateCleanup");
					}
					$otherMenu.one("transitionend", () => $otherMenu.hide());
				}
			});

		if ($menu.hasClass("active")) {
			this.closeDropdown($menu);
		} else {
			this.openDropdown($toggle, $menu);
		}
	}

	openDropdown($toggle, $menu) {
		try {
			if ($toggle.attr("data-dropdown-hover") === "true") {
				$("[data-dropdown].active").each((_, el) => {
					const $otherMenu = $(el);
					if ($otherMenu[0] === $menu[0]) return;

					const otherName = $otherMenu.data("dropdown");
					const $otherToggle = $(
						`[data-dropdown-open="${otherName}"]`
					);
					if ($otherToggle.attr("data-dropdown-hover") === "true") {
						this.closeDropdown($otherMenu);
					}
				});
			}

			const $originalParent = $menu.parent();
			$menu.data("originalParent", $originalParent);

			$menu.appendTo("body");
			$menu.show().addClass("active");
			$("body").addClass("no-scroll");

			this.positionDropdown($toggle, $menu);
		} catch (error) {
			console.error("Error opening dropdown:", error);
		}
	}

	positionDropdown($toggle, $menu) {
		if (!$toggle[0] || !$menu[0] || !window.FloatingUIDOM) return;

		const updatePosition = () => {
			if (!$toggle[0] || !$menu[0]) return;

			window.FloatingUIDOM.computePosition($toggle[0], $menu[0], {
				placement: "bottom",
				middleware: [
					window.FloatingUIDOM.offset(10),
					window.FloatingUIDOM.flip({
						fallbackPlacements: ["top"],
					}),
					window.FloatingUIDOM.shift({ padding: 5 }),
				],
			}).then(({ x, y, placement }) => {
				if (!$menu[0]) return; // Check if menu still exists

				Object.assign($menu[0].style, {
					left: `${x}px`,
					top: `${y}px`,
					position: "absolute",
					zIndex: 9999,
				});
				$menu.attr("data-placement", placement);
			});
		};

		updatePosition();

		// Store cleanup function to prevent memory leaks
		try {
			const cleanup = window.FloatingUIDOM.autoUpdate(
				$toggle[0],
				$menu[0],
				updatePosition
			);
			$menu.data("autoUpdateCleanup", cleanup);
		} catch (error) {
			console.error("Error setting up FloatingUI:", error);
		}
	}

	closeDropdown($menu) {
		if (!$menu || !$menu.length) return;

		try {
			$menu.removeClass("active");

			// Clean up FloatingUI autoUpdate
			const cleanup = $menu.data("autoUpdateCleanup");
			if (cleanup && typeof cleanup === "function") {
				cleanup();
				$menu.removeData("autoUpdateCleanup");
			}

			$menu.one("transitionend", function () {
				$menu.hide();
				$("body").removeClass("no-scroll");

				// Return to original parent
				const $originalParent = $menu.data("originalParent");
				if ($originalParent && $originalParent.length) {
					$menu.appendTo($originalParent);
					$menu.removeData("originalParent");
				}
			});
		} catch (error) {
			console.error("Error closing dropdown:", error);
			// Fallback in case of error
			$menu.hide();
			$("body").removeClass("no-scroll");
		}
	}

	closeAllDropdowns() {
		$("[data-dropdown].active").each((_, element) => {
			this.closeDropdown($(element));
		});
	}
}

/**
 * Theme management
 */
class ThemeManager {
	constructor() {
		this.initTheme();
	}

	initTheme() {
		this.themeToggleButton = $("#theme-toggle");
		this.sunIcon = this.themeToggleButton.find(".sun-icon");
		this.moonIcon = this.themeToggleButton.find(".moon-icon");

		const changeThemeEnabled =
			document
				.querySelector('meta[name="change-theme"]')
				?.getAttribute("content") === "true";

		if (!changeThemeEnabled) {
			this.themeToggleButton.hide();
		}

		const defaultTheme =
			document
				.querySelector('meta[name="default-theme"]')
				?.getAttribute("content") || "dark";
		const currentTheme = changeThemeEnabled
			? getCookie("theme") || this.detectSystemTheme() || defaultTheme
			: defaultTheme;
		this.applyTheme(currentTheme);

		this.themeToggleButton.on("click", () => {
			const changeThemeEnabled =
				document
					.querySelector('meta[name="change-theme"]')
					?.getAttribute("content") === "true";

			if (!changeThemeEnabled) {
				return;
			}

			const currentTheme =
				$("html").attr("data-theme") === "light" ? "dark" : "light";
			this.applyTheme(currentTheme);
			setCookie("theme", currentTheme, 365);
		});

		this.initSystemThemeListener();

		window.addEventListener("switch-theme", (event) => {
			const theme = event.detail?.theme;
			if (theme) {
				this.applyTheme(theme);
				setCookie("theme", theme, 365);
			}
		});
	}

	updateIcons(theme) {
		if (theme === "dark") {
			this.sunIcon.hide(100);
			this.moonIcon.show(100);
		} else {
			this.moonIcon.hide(100);
			this.sunIcon.show(100);
		}
	}

	applyTheme(theme) {
		$("html").attr("data-theme", theme);
		this.updateIcons(theme);
	}

	detectSystemTheme() {
		return window.matchMedia("(prefers-color-scheme: dark)").matches
			? "dark"
			: "light";
	}

	initSystemThemeListener() {
		const darkModeMediaQuery = window.matchMedia(
			"(prefers-color-scheme: dark)"
		);
		if (darkModeMediaQuery.addEventListener) {
			darkModeMediaQuery.addEventListener("change", (e) => {
				if (!getCookie("theme")) {
					const defaultTheme =
						document
							.querySelector('meta[name="default-theme"]')
							?.getAttribute("content") || "dark";
					const newTheme = e.matches ? "dark" : "light";
					const changeThemeEnabled =
						document
							.querySelector('meta[name="change-theme"]')
							?.getAttribute("content") === "true";
					const finalTheme = changeThemeEnabled
						? newTheme
						: defaultTheme;
					this.applyTheme(finalTheme);
				}
			});
		}
	}
}

/**
 * Form input management
 */
class FormManager {
	constructor() {
		// No initialization needed
	}

	initInputHandlers() {
		// Clear input button
		$(document).on("click", ".clear-input", (e) => {
			const inputName = $(e.currentTarget).data("input");
			$(`input[name="${inputName}"]`).val("");
			$(e.currentTarget).hide();
		});

		// Input validation
		$(document).on("input", "input", function () {
			const errorElement = $(this)
				.closest(".input-wrapper")
				.find(".input__error");

			if ($(this).val().length > 0) {
				setTimeout(() => {
					$(this)
						.closest(".input-wrapper > .input__field-container")
						.removeClass("has-error");
					errorElement.hide();
				}, 400);
			} else {
				errorElement.show();
			}
		});

		// Numeric input handling
		$(document).on("keypress", 'input[data-numeric="true"]', function (e) {
			const withDots = $(this).data("with-dots");
			const charCode = e.which || e.keyCode;

			if (
				(charCode < 48 || charCode > 57) &&
				!(withDots && charCode === 46) &&
				charCode > 31
			) {
				e.preventDefault();
			}
		});

		// Min/max validation
		$(document).on("blur", "input[data-min], input[data-max]", function () {
			const min = parseFloat($(this).data("min"));
			const max = parseFloat($(this).data("max"));
			const value = parseFloat($(this).val());

			if (!isNaN(min) && value < min) $(this).val(min);
			if (!isNaN(max) && value > max) $(this).val(max);
		});

		// Initial state
		$(".clear-input").hide();
	}
}

/**
 * Confirmation dialog management
 */
class ConfirmationManager {
	constructor() {
		this.confirmTypes = {
			accent: {
				buttonClass: "btn-accent",
				iconClass: "icon-accent",
			},
			primary: {
				buttonClass: "btn-primary",
				iconClass: "icon-primary",
			},
			error: {
				buttonClass: "btn-error",
				iconClass: "icon-error",
			},
			warning: {
				buttonClass: "btn-warning",
				iconClass: "icon-warning",
			},
			info: {
				buttonClass: "btn-info",
				iconClass: "icon-info",
			},
			success: {
				buttonClass: "btn-success",
				iconClass: "icon-success",
			},
		};

		this.confirmedActions = new Set();
		this.initConfirmEvents();
	}

	initConfirmEvents() {
		// HTMX confirmation
		$(document).on("click", "[hx-flute-confirm]", (event) => {
			event.preventDefault();

			const $triggerElement = $(event.currentTarget);
			const confirmMessage = $triggerElement.attr("hx-flute-confirm");
			const confirmType =
				$triggerElement.attr("hx-flute-confirm-type") || "error";
			const actionKey = $triggerElement.attr("hx-flute-action-key");
			const withoutTrigger = $triggerElement.attr(
				"hx-flute-without-trigger"
			);

			if (actionKey && this.confirmedActions.has(actionKey)) {
				htmx.trigger($triggerElement[0], "confirmed");
				return;
			}

			this.showConfirmDialog({
				message: confirmMessage,
				type: confirmType,
				actionKey: actionKey,
				withoutTrigger: withoutTrigger,
				onConfirm: () => {
					htmx.trigger($triggerElement[0], "confirmed");
					if (actionKey) {
						this.confirmedActions.add(actionKey);
					}
				},
				onCancel: () => {
					if (actionKey) {
						this.confirmedActions.delete(actionKey);
					}
				},
			});
		});

		// YoYo confirmation
		document.addEventListener("confirm", (event) => {
			const {
				message,
				title,
				confirmText,
				cancelText,
				type,
				actionKey,
				action,
				originalRequestData,
				withoutTrigger,
			} = event.detail[0];
			const yoyoComponent = event.detail.elt;

			if (!yoyoComponent) {
				console.error("No component found for confirmation event");
				return;
			}

			event.preventDefault();

			this.showConfirmDialog({
				message,
				title,
				confirmText,
				cancelText,
				type,
				withoutTrigger,
				onConfirm: () => {
					this.handleYoyoConfirmation(
						yoyoComponent,
						action,
						actionKey,
						originalRequestData
					);
				},
			});
		});
	}

	showConfirmDialog(options) {
		const {
			message,
			title,
			confirmText,
			cancelText,
			type = "error",
			withoutTrigger,
			onConfirm,
			onCancel,
		} = options;

		const currentType =
			this.confirmTypes[type] || this.confirmTypes["error"];

		// Set message
		$("#confirmation-dialog-message").text(message);

		// Configure confirm button style
		let $confirmButton = $("#confirmation-dialog-confirm");
		let $cancelButton = $("#confirmation-dialog-cancel");
		let $title = $("#confirmation-dialog-title");

		$confirmButton.removeClass(
			"btn-accent btn-primary btn-error btn-warning btn-info"
		);
		$confirmButton.addClass(currentType.buttonClass);

		// Handle custom text
		if (confirmText) {
			$confirmButton.attr("old-text", $confirmButton.text());
			$confirmButton.text(confirmText);
		}

		// Handle without trigger option
		if (withoutTrigger) {
			$confirmButton.hide();
		}

		// Set icon
		let $iconContainer = $("#confirmation-dialog-icon");
		$iconContainer.children().hide();
		$iconContainer.find("." + currentType.iconClass).show();

		// Custom cancel text
		if (cancelText) {
			$cancelButton.attr("old-text", $cancelButton.text());
			$cancelButton.text(cancelText);
		}

		// Custom title
		if (title) {
			$title.attr("old-text", $title.text());
			$title.text(title);
		}

		let confirmHandled = false;
		let cancelHandled = false;

		// Confirm action
		$confirmButton.on("click", () => {
			if (confirmHandled) return;
			confirmHandled = true;

			$confirmButton.off("click");
			$cancelButton.off("click");

			closeModal("confirmation-dialog");

			if (typeof onConfirm === "function") {
				onConfirm();
			}

			if (confirmText)
				$confirmButton.text($confirmButton.attr("old-text"));
			if (cancelText) $cancelButton.text($cancelButton.attr("old-text"));
			if (title) $title.text($title.attr("old-text"));

			$confirmButton.removeClass(currentType.buttonClass);

			confirmHandled = false;
			cancelHandled = false;
		});

		$cancelButton.on("click", () => {
			if (cancelHandled) return;
			cancelHandled = true;

			$confirmButton.off("click");
			$cancelButton.off("click");

			closeModal("confirmation-dialog");

			if (typeof onCancel === "function") {
				onCancel();
			}

			setTimeout(() => {
				if (confirmText)
					$confirmButton.text($confirmButton.attr("old-text"));
				if (cancelText)
					$cancelButton.text($cancelButton.attr("old-text"));
				if (title) $title.text($title.attr("old-text"));

				$confirmButton.removeClass(currentType.buttonClass);

				if (withoutTrigger) {
					$confirmButton.show();
				}

				confirmHandled = false;
				cancelHandled = false;
			}, 300);
		});

		let $closeButton = $("#confirmation-dialog").find(".modal__close");
		$closeButton.off("click");
		$closeButton.on("click", () => {
			$confirmButton.off("click");
			$cancelButton.off("click");
			$closeButton.off("click");

			setTimeout(() => {
				if (confirmText)
					$confirmButton.text($confirmButton.attr("old-text"));
				if (cancelText)
					$cancelButton.text($cancelButton.attr("old-text"));
				if (title) $title.text($title.attr("old-text"));

				$confirmButton.removeClass(currentType.buttonClass);

				if (withoutTrigger) {
					$confirmButton.show();
				}

				confirmHandled = false;
				cancelHandled = false;
			}, 300);
		});

		// Show modal
		openModal("confirmation-dialog");
	}

	handleYoyoConfirmation(
		yoyoComponent,
		action,
		actionKey,
		originalRequestData
	) {
		if (!yoyoComponent || !action) return;

		try {
			const requestData = originalRequestData || {};

			if (requestData["confirmed_action"]) {
				if (Array.isArray(requestData["confirmed_action"])) {
					requestData["confirmed_action"].push(actionKey);
				} else {
					requestData["confirmed_action"] = [
						requestData["confirmed_action"],
						actionKey,
					];
				}
			} else {
				requestData["confirmed_action"] = actionKey;
			}

			const headers = {
				"Content-Type": "application/x-www-form-urlencoded",
				"X-Requested-With": "XMLHttpRequest",
				"X-HX-Request": "true",
				"X-Csrf-Token": document
					.querySelector('meta[name="csrf-token"]')
					.getAttribute("content"),
			};

			const componentName = yoyoComponent.getAttribute("yoyo:name");
			requestData["component"] = `${componentName}/${action}`;

			if (requestData["actionArgs"]) {
				requestData["actionArgs"] = JSON.stringify(
					requestData["actionArgs"]
				);
			}

			const formData = new URLSearchParams(requestData).toString();

			const targetSelector = yoyoComponent.getAttribute("id")
				? `#${yoyoComponent.getAttribute("id")}`
				: null;

			if (targetSelector) {
				const xhr = new XMLHttpRequest();
				xhr.open("POST", Yoyo.url, true);

				Object.keys(headers).forEach((key) => {
					xhr.setRequestHeader(key, headers[key]);
				});

				xhr.onload = () => {
					if (xhr.status >= 200 && xhr.status < 300) {
						htmx.trigger(document.body, "htmx:afterRequest", {
							target: yoyoComponent,
							xhr: xhr,
						});

						if (
							xhr
								.getAllResponseHeaders()
								.indexOf("hx-trigger") !== -1
						) {
							const triggerHeader =
								xhr.getResponseHeader("hx-trigger");
							if (triggerHeader) {
								try {
									const triggers = JSON.parse(triggerHeader);
									Object.keys(triggers).forEach(
										(eventName) => {
											htmx.trigger(
												document.body,
												eventName,
												triggers[eventName]
											);
										}
									);
								} catch (e) {
									htmx.trigger(document.body, triggerHeader);
								}
							}
						}

						const emitHeader = xhr.getResponseHeader("yoyo-emit");
						if (emitHeader) {
							Yoyo.processEmitEvents(yoyoComponent, emitHeader);
						}

						const browserEventsHeader =
							xhr.getResponseHeader("yoyo-browser-event");
						if (browserEventsHeader) {
							Yoyo.processBrowserEvents(browserEventsHeader);
						}

						if (xhr.responseText.trim() !== "") {
							const temp = document.createElement("div");
							temp.innerHTML = xhr.responseText;

							const responseEl = temp.firstElementChild;

							if (responseEl) {
								yoyoComponent.outerHTML = responseEl.outerHTML;
								htmx.process(
									document.querySelector(targetSelector)
								);
							}
						} else {
							YoyoEngine.trigger(yoyoComponent, action);
							htmx.trigger(document.body, "htmx:afterSwap", {
								target: yoyoComponent,
							});
						}
					}
				};

				xhr.send(formData);
			}
		} catch (error) {
			console.error("Error in YoYo confirmation handling:", error);
		}
	}
}

let app;
let notyf;

$(document).ready(function () {
	app = new FluteApp();
	notyf = app.notyf;
});
