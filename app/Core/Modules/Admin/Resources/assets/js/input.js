$(document).ready(function () {
	let iconPicker = null;
	const iconPacksData = {};
	const categorizedPacks = {};
	const lastCategoryByInput = new WeakMap();
	const iconCache = getIconCache();
	const pickrByInputId = new Map();

	initColorPickers();
	initIconPickers();


	function getIconCache() {
		try {
			return JSON.parse(localStorage.getItem("iconCache")) || {};
		} catch (e) {
			return {};
		}
	}

	function initColorPickers(root = document) {
		if (!window.Pickr) {
			setTimeout(() => {
				// After delay, verify root is still in DOM (it may have been swapped out)
				if (root !== document && root.isConnected === false) return;
				initColorPickers(root);
			}, 100);
			return;
		}

		const containers = (root || document).querySelectorAll(".color-inline-picker");

		containers.forEach((container) => {
			if (container._pickrInit) return;
			if (!container.isConnected) return;

			const inputId = container.getAttribute("data-input-id");
			const input = document.getElementById(inputId);
			if (!input || !input.isConnected) return;

			container._pickrInit = true;

			// Ensure collapsed state (already set in HTML, enforce for dynamic content without transition flash)
			if (!container.classList.contains("is-collapsed")) {
				container.style.transition = "none";
				container.classList.add("is-collapsed");
				container.offsetHeight; // force reflow
				container.style.transition = "";
			}
			const swatch = container.parentElement.querySelector('.color-inline-swatch[data-input-id="' + inputId + '"]');
			if (swatch) {
				swatch.style.cursor = "pointer";
				swatch.addEventListener("click", function () {
					container.classList.toggle("is-collapsed");
				});
			}

			const seed = (input.value || input.dataset.color || "#42445A").trim();

			// Destroy existing instance if any
			const existing = pickrByInputId.get(inputId);
			if (existing) {
				try { existing.destroy(); } catch (_) {}
				pickrByInputId.delete(inputId);
			}

			// Remove leftover trigger elements from previous instances
			container.querySelectorAll(".pickr-inline-trigger").forEach((el) => el.remove());

			// Create a hidden trigger element inside the inline container
			const trigger = document.createElement("div");
			trigger.className = "pickr-inline-trigger";
			container.appendChild(trigger);

			try {
				const pickr = Pickr.create({
					el: trigger,
					theme: "nano",
					container: container,
					default: seed,
					inline: true,
					showAlways: true,
					useAsButton: false,
					comparison: false,
					lockOpacity: false,
					swatches: [
						"#000000", "#FFFFFF",
						"#FF453A", "#FF9F0A", "#FFD60A",
						"#34C759", "#64D2FF", "#0A84FF",
						"#5856D6", "#BF5AF2", "#FF375F",
						"#8E8E93",
					],
					components: {
						preview: false,
						opacity: true,
						hue: true,
						interaction: {
							input: false,
							cancel: false,
							clear: false,
							save: false,
						},
					},
				});

				pickrByInputId.set(inputId, pickr);

				// Update swatch indicator
				const swatch = container.parentElement.querySelector('.color-inline-swatch[data-input-id="' + inputId + '"]');

				const setInputSafely = (val) => {
					input._colorSyncingFromPickr = true;
					input.value = val;
					if (swatch) swatch.style.setProperty("--swatch-color", val || "transparent");
					input.dispatchEvent(new Event("input", { bubbles: true }));
					input.dispatchEvent(new Event("change", { bubbles: true }));
					setTimeout(() => { input._colorSyncingFromPickr = false; }, 0);
				};

				pickr.on("change", (color) => {
					const hex = color ? color.toHEXA().toString() : "";
					setInputSafely(hex);
				});

				// Sync from input to pickr (manual typing/paste)
				const syncFromInput = () => {
					if (input._colorSyncingFromPickr) return;
					const val = (input.value || "").trim();
					if (!val) return;
					try {
						const current = pickr.getColor();
						const curHex = current ? current.toHEXA().toString() : "";
						if (curHex.toLowerCase() !== val.toLowerCase()) {
							pickr.setColor(val);
						}
						if (swatch) swatch.style.setProperty("--swatch-color", val);
					} catch (_) {}
				};
				input.addEventListener("input", syncFromInput);

				input.addEventListener("paste", (e) => {
					const text = (e.clipboardData || window.clipboardData).getData("text");
					if (!text) return;
					const normalized = normalizeColor(text);
					if (normalized) {
						e.preventDefault();
						input._colorSyncingFromPickr = true;
						input.value = normalized;
						try { pickr.setColor(normalized); } catch (_) {}
						if (swatch) swatch.style.setProperty("--swatch-color", normalized);
						input.dispatchEvent(new Event("input", { bubbles: true }));
						input.dispatchEvent(new Event("change", { bubbles: true }));
						setTimeout(() => { input._colorSyncingFromPickr = false; }, 0);
					}
				});

				try { pickr.setColor(seed); } catch (_) {}
				if (swatch) swatch.style.setProperty("--swatch-color", seed);
			} catch (error) {
				console.error("Failed to create inline Pickr:", error);
			}
		});
	}

	window.initColorPickers = function (root) {
		try {
			initColorPickers(root || document);
		} catch (e) {}
	};

	window.initIconPickers = function (root) {
		try {
			initIconPickers(root || document);
		} catch (e) {}
	};

	function normalizeColor(text) {
		const t = (text || "").trim();
		// Hex short/long
		const hex = t.match(/^#?[0-9a-fA-F]{3,8}$/);
		if (hex) {
			return t.startsWith("#") ? t : `#${t}`;
		}
		// rgb/rgba
		const rgb = t.match(/^rgba?\(([^)]+)\)$/i);
		if (rgb) return t;
		// hsl/hsla
		const hsl = t.match(/^hsla?\(([^)]+)\)$/i);
		if (hsl) return t;
		return null;
	}

	// Cleanup Pickr instances when HTMX is about to remove elements
	function cleanupPickrsIn(el) {
		if (!el) return;
		const candidates = [];
		if (el.matches && el.matches(".color-inline-picker")) candidates.push(el);
		if (el.querySelectorAll)
			candidates.push(...el.querySelectorAll(".color-inline-picker"));
		candidates.forEach((container) => {
			const inputId = container.getAttribute("data-input-id");
			const instance = inputId ? pickrByInputId.get(inputId) : null;
			if (instance) {
				// Use destroy() only — HTMX will remove the DOM elements itself.
				// destroyAndRemove() can interfere with HTMX's own cleanup.
				try { instance.destroy(); } catch (_) {}
			}
			if (inputId) pickrByInputId.delete(inputId);
			delete container._pickrInit;
		});
	}

	document.body.addEventListener("htmx:beforeCleanupElement", (evt) => {
		cleanupPickrsIn(evt && evt.target ? evt.target : null);
	});

	// Also clean up on beforeSwap for swap strategies that may not fire beforeCleanupElement
	document.body.addEventListener("htmx:beforeSwap", (evt) => {
		const target = evt.detail && evt.detail.target ? evt.detail.target : null;
		cleanupPickrsIn(target);
	});

	function saveIconCache() {
		try {
			const currentCache = { ...iconCache };
			const cacheSize = JSON.stringify(currentCache).length;
			if (cacheSize > 5 * 1024 * 1024) {
				const keys = Object.keys(currentCache);
				for (let i = 0; i < keys.length / 2; i++) {
					delete currentCache[keys[i]];
				}
			}
			localStorage.setItem("iconCache", JSON.stringify(currentCache));
		} catch (e) {
			console.warn("Не удалось сохранить кеш иконок:", e);
		}
	}

	function initIconPickers(root) {
		const scope = (root instanceof Element) ? root : document;
		const iconInputs = scope.querySelectorAll(".input__field-icon");

		if (!iconInputs.length) return;

		iconInputs.forEach(function (input) {
			if (input.hasIconPickerInitialized) return;

			input.hasIconPickerInitialized = true;

			const iconPacks = JSON.parse(input.dataset.iconPacks || "[]");

			// Auto-opening picker on focus prevented to allow manual SVG input.

			input.addEventListener("input", function () {
				const event = new Event("change", { bubbles: true });
				input.dispatchEvent(event);

				if (!this.value) {
					const container = this.closest(".icon-input-container");
					if (container) {
						const preview = container.querySelector(
							".icon-input-preview"
						);
						if (preview) {
							preview.innerHTML = "";
						}
					}
				}
			});

			const container = input.closest(".icon-input-container");
			if (container) {
				const preview = container.querySelector(".icon-input-preview");
				if (preview) {
					preview.addEventListener("click", function (event) {
						event.stopPropagation();
						createAndOpenPicker(input, iconPacks);
					});
				}

				const pickerBtn = container.querySelector(
					".input__icon-picker-btn"
				);
				if (pickerBtn) {
					pickerBtn.addEventListener("click", function (event) {
						event.stopPropagation();
						createAndOpenPicker(input, iconPacks);
					});
				}
			}
		});

		function createAndOpenPicker(input, iconPacks) {
			if (!iconPicker) {
				iconPicker = document.createElement("div");
				iconPicker.id = "iconPickerModal";
				iconPicker.className = "icon-picker";

				iconPicker.innerHTML = `
                    <div class="icon-picker__backdrop"></div>
                    <div class="icon-picker__dialog">
                        <div class="icon-picker__header">
                            <div class="icon-picker__search">
                                <svg class="icon-picker__search-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M21 21L16.65 16.65M19 11C19 15.4183 15.4183 19 11 19C6.58172 19 3 15.4183 3 11C3 6.58172 6.58172 3 11 3C15.4183 3 19 6.58172 19 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <input type="text" placeholder="" class="icon-picker__search-input">
                            </div>
                            <button type="button" class="icon-picker__close" aria-label="Close">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                        <div class="icon-picker__body">
                            <div class="icon-picker__categories"></div>
                            <div class="icon-picker__styles"></div>
                            <div class="icon-picker__content"></div>
                        </div>
                    </div>
                `;

				document.body.appendChild(iconPicker);

				const backdrop = iconPicker.querySelector(".icon-picker__backdrop");
				backdrop.addEventListener("click", hideIconPicker);

				const searchInput = iconPicker.querySelector(
					".icon-picker__search-input"
				);

				// Set placeholder safely (translate() can return HTML spans)
				const searchPlaceholder = translate('def.search');
				if (typeof searchPlaceholder === 'string' && !searchPlaceholder.includes('<')) {
					searchInput.placeholder = searchPlaceholder + '...';
				} else {
					searchInput.placeholder = 'Search...';
				}

				searchInput.addEventListener(
					"input",
					debounce(function () {
						const searchText = this.value.toLowerCase();
						let packName;
						let styleName;

						const activePackTab = iconPicker.querySelector(
							".icon-picker__tab.active"
						);
						const activeStyle = iconPicker.querySelector(
							".icon-picker__style.active"
						);
						styleName = activeStyle
							? activeStyle.dataset.style
							: null;

						if (activePackTab) {
							packName = activePackTab.dataset.pack;
						} else {
							const firstCategoryElem = iconPicker.querySelector(
								".icon-picker__category.active"
							);
							if (firstCategoryElem) {
								const categoryKey =
									firstCategoryElem.dataset.category;
								const packsInCategory =
									categorizedPacks[categoryKey];
								if (
									packsInCategory &&
									packsInCategory.length > 0
								) {
									const firstPackData = packsInCategory[0];
									if (firstPackData) {
										packName = firstPackData.prefix;
									}
								}
							}
						}

						if (!packName) {
							console.warn(
								"Icon search: Could not determine active pack. Aborting search."
							);
							const contentContainer = iconPicker.querySelector(
								".icon-picker__content"
							);
							if (contentContainer) {
								contentContainer.innerHTML =
									'<div class="icon-picker__error">Выберите пакет иконок для поиска.</div>';
							}
							return;
						}

						if (searchText.length < 2) {
							renderIconsForPack(packName, null, styleName);
							return;
						}
						if (styleName) {
							searchIcons(packName, searchText, styleName);
						} else {
							searchIcons(packName, searchText);
						}
					}, 300)
				);

				const closeButton = iconPicker.querySelector(
					".icon-picker__close"
				);
				closeButton.addEventListener("click", hideIconPicker);

				// Backdrop click handles closing — no need for document click listener

				iconPicker.addEventListener("keydown", function (e) {
					if (e.key === "Escape") {
						hideIconPicker();
					} else if (e.key === "Tab") {
						const searchInput = iconPicker.querySelector(
							".icon-picker__search-input"
						);
						if (document.activeElement !== searchInput) {
							e.preventDefault();
							searchInput.focus({ preventScroll: true });
						}
					}
				});

				// Event delegation for icon clicks
				const contentEl = iconPicker.querySelector(".icon-picker__content");
				contentEl.addEventListener("click", function (e) {
					const iconEl = e.target.closest(".icon-picker__icon");
					if (iconEl && iconEl.dataset.iconPath) {
						selectIcon(iconEl.dataset.iconPath);
					}
				});

				// Infinite scroll
				let isLoadingMore = false;
				contentEl.addEventListener("scroll", function () {
					if (isLoadingMore) return;
					const threshold = 100;
					if (contentEl.scrollTop + contentEl.clientHeight >= contentEl.scrollHeight - threshold) {
						isLoadingMore = true;
						loadNextPage().finally(() => {
							isLoadingMore = false;
						});
					}
				});

				function loadNextPage() {
					const activeTab = iconPicker.querySelector(".icon-picker__tab.active");
					if (!activeTab) return Promise.resolve();
					const packName = activeTab.dataset.pack;
					const activeStyle = iconPicker.querySelector(".icon-picker__style.active");
					const styleName = activeStyle ? activeStyle.dataset.style : null;
					const cacheKey = getCacheKey(packName, styleName);
					const packData = iconPacksData[cacheKey];
					if (!packData) return Promise.resolve();

					if (packData.searching) {
						const currentPage = packData.currentPageSearch || 1;
						const totalPages = packData.totalPagesSearch || 1;
						if (currentPage >= totalPages) return Promise.resolve();
						packData.currentPageSearch = currentPage + 1;
						appendSearchResults(packName, styleName);
						return Promise.resolve();
					} else {
						const currentPage = packData.currentPage || 1;
						const totalPages = packData.totalPages || 1;
						if (currentPage >= totalPages) return Promise.resolve();
						const nextPage = currentPage + 1;
						packData.currentPage = nextPage;
						if (packData.hasPage[nextPage]) {
							appendIconsForPage(packName, nextPage, styleName);
							return Promise.resolve();
						}
						return loadAndAppendPage(packName, nextPage, styleName);
					}
				}

				// Auto-fill: keep loading pages until scrollbar appears or no more pages
				function fillUntilScrollable() {
					requestAnimationFrame(() => {
						if (contentEl.scrollHeight <= contentEl.clientHeight && !isLoadingMore) {
							isLoadingMore = true;
							loadNextPage().then(() => {
								isLoadingMore = false;
								fillUntilScrollable();
							}).catch(() => {
								isLoadingMore = false;
							});
						}
					});
				}

				// Observe content changes to trigger auto-fill
				const fillObserver = new MutationObserver(() => {
					fillUntilScrollable();
				});
				fillObserver.observe(contentEl, { childList: true });
			}

			iconPicker.currentInput = input;

			const categoriesContainer = iconPicker.querySelector(
				".icon-picker__categories"
			);
			categoriesContainer.innerHTML = "";

			// Show modal immediately with skeleton loader
			iconPicker.classList.add("active");
			document.body.style.overflow = "hidden";
			positionPicker(input);

			if (Object.keys(categorizedPacks).length === 0) {
				iconPicker.querySelector(".icon-picker__content").innerHTML =
					createSkeletonLoader();
				fetch(u("admin/api/icons/packages"))
					.then((response) => response.json())
					.then((packages) => {
						packages.forEach((pack) => {
							const category = pack.category || "Другие";
							if (!categorizedPacks[category]) {
								categorizedPacks[category] = [];
							}
							categorizedPacks[category].push(pack);
						});
						createCategories();
					})
					.catch((error) => {
						console.error(
							"Ошибка при загрузке пакетов иконок:",
							error
						);
						iconPicker.querySelector(
							".icon-picker__content"
						).innerHTML =
							'<div class="icon-picker__error">Не удалось загрузить пакеты иконок</div>';
					});
			} else {
				createCategories();
			}
		}

		function createCategories() {
			const categoriesContainer = iconPicker.querySelector(
				".icon-picker__categories"
			);

			categoriesContainer.innerHTML = "";

			const categories = Object.keys(categorizedPacks);
			const saved =
				lastCategoryByInput.get(iconPicker.currentInput) ||
				categories[0];
			categories.forEach((category) => {
				const categoryEl = document.createElement("div");
				categoryEl.className = "icon-picker__category";
				categoryEl.textContent = category;
				categoryEl.dataset.category = category;

				if (category === saved) {
					categoryEl.classList.add("active");
					createTabsForCategory(category);
				}
				categoryEl.addEventListener("click", function () {
					categoriesContainer
						.querySelectorAll(".icon-picker__category")
						.forEach((c) => c.classList.remove("active"));
					this.classList.add("active");
					lastCategoryByInput.set(
						iconPicker.currentInput,
						this.dataset.category
					);
					createTabsForCategory(this.dataset.category);
					reapplySearch();
				});

				categoriesContainer.appendChild(categoryEl);
			});
		}

		function createTabsForCategory(category) {
			const stylesContainer = iconPicker.querySelector(
				".icon-picker__styles"
			);

			stylesContainer.innerHTML = "";

			const packs = categorizedPacks[category] || [];

			packs.forEach((pack, index) => {
				const tab = document.createElement("div");
				tab.className = "icon-picker__tab";
				tab.textContent = pack.name;
				tab.dataset.pack = pack.prefix;

				if (index === 0) {
					tab.classList.add("active");
					loadIconsForPack(pack.prefix);

					if (pack.categories && pack.categories.length > 0) {
						createStylesForPack(pack.prefix, pack.categories);
					}
				}

				tab.addEventListener("click", function () {
					stylesContainer.innerHTML = "";
					const tabs =
						iconPicker.querySelectorAll(".icon-picker__tab");
					tabs.forEach((t) => t.classList.remove("active"));
					this.classList.add("active");
					const packPrefix = this.dataset.pack;

					const pack = categorizedPacks[category].find(
						(p) => p.prefix === packPrefix
					);

					if (pack && pack.categories && pack.categories.length > 0) {
						createStylesForPack(packPrefix, pack.categories);
					} else {
						stylesContainer.innerHTML = "";
						stylesContainer.style.display = "none";
					}

					reapplySearch();
				});

				stylesContainer.appendChild(tab);
			});
		}

		function createStylesForPack(packPrefix, categories) {
			const stylesContainer = iconPicker.querySelector(
				".icon-picker__styles"
			);
			stylesContainer.innerHTML = "";

			if (!categories || categories.length === 0) {
				stylesContainer.style.display = "none";
				return;
			}

			stylesContainer.style.display = "flex";

			const allStyle = document.createElement("div");
			allStyle.className = "icon-picker__style active";
			allStyle.textContent = "All";
			allStyle.dataset.style = "";
			allStyle.addEventListener("click", function () {
				stylesContainer
					.querySelectorAll(".icon-picker__style")
					.forEach((s) => {
						s.classList.remove("active");
					});
				this.classList.add("active");

				reapplySearch();
			});
			stylesContainer.appendChild(allStyle);

			categories.forEach((style) => {
				const styleEl = document.createElement("div");
				styleEl.className = "icon-picker__style";
				styleEl.textContent =
					style.charAt(0).toUpperCase() + style.slice(1);
				styleEl.dataset.style = style;

				styleEl.addEventListener("click", function () {
					stylesContainer
						.querySelectorAll(".icon-picker__style")
						.forEach((s) => {
							s.classList.remove("active");
						});
					this.classList.add("active");

					reapplySearch();
				});

				stylesContainer.appendChild(styleEl);
			});
		}

		function positionPicker(input) {
			// Modal is centered via CSS — no positioning needed
			requestAnimationFrame(() => {
				const searchInput = iconPicker.querySelector(
					".icon-picker__search-input"
				);
				if (searchInput && document.activeElement !== searchInput) {
					searchInput.focus({ preventScroll: true });
				}
			});
		}

		function hideIconPicker() {
			if (iconPicker) {
				iconPicker.classList.remove("active");
				document.body.style.overflow = "";
			}
		}

		function getCacheKey(packPrefix, styleName = null) {
			return packPrefix + (styleName ? `-${styleName}` : "");
		}

		function searchIcons(packPrefix, searchText, styleName = null) {
			if (searchText.length < 2) {
				if (packPrefix) {
					renderIconsForPack(packPrefix, null, styleName);
				}
				return;
			}

			const contentContainer = iconPicker.querySelector(
				".icon-picker__content"
			);
			contentContainer.innerHTML = createSkeletonLoader();

			const cacheKey = getCacheKey(packPrefix, styleName);

			let url = `admin/api/icons/search?prefix=${packPrefix}&q=${encodeURIComponent(
				searchText
			)}`;
			if (styleName) {
				url += `&category=${styleName}`;
			}

			fetch(u(url))
				.then((response) => response.json())
				.then((data) => {
					if (!iconPacksData[cacheKey]) {
						iconPacksData[cacheKey] = {
							currentPage: 1,
							totalPages: 1,
							icons: [],
							limit: 150,
							hasPage: {},
						};
					}

					const packData = iconPacksData[cacheKey];

					const searchResults = data.icons.map((icon) => ({
						path: icon.path,
						svg: icon.svg,
						displayName: icon.displayName,
					}));

					packData.searching = true;
					packData.searchQuery = searchText;
					packData.searchResults = searchResults;
					packData.totalPagesSearch = Math.ceil(
						searchResults.length / packData.limit
					);
					packData.currentPageSearch = 1;

					renderSearchResults(packPrefix, searchText, styleName);
				})
				.catch((error) => {
					console.error(
						`Ошибка при поиске иконок для пакета ${packPrefix}:`,
						error
					);
					contentContainer.innerHTML =
						'<div class="icon-picker__error">Не удалось выполнить поиск иконок</div>';
				});
		}

		function renderSearchResults(packPrefix, searchText, styleName = null) {
			const cacheKey = getCacheKey(packPrefix, styleName);
			const packData = iconPacksData[cacheKey];

			if (!packData || !packData.searchResults) return;

			const contentContainer = iconPicker.querySelector(
				".icon-picker__content"
			);

			contentContainer.innerHTML = "";
			contentContainer.scrollTop = 0;

			packData.currentPageSearch = 1;

			const iconsToDisplay = packData.searchResults.slice(0, packData.limit);

			if (iconsToDisplay.length === 0) {
				contentContainer.innerHTML =
					'<div class="icon-picker__empty">Ничего не найдено</div>';
				return;
			}

			appendIconElements(contentContainer, iconsToDisplay);
		}

		function appendSearchResults(packPrefix, styleName = null) {
			const cacheKey = getCacheKey(packPrefix, styleName);
			const packData = iconPacksData[cacheKey];
			if (!packData || !packData.searchResults) return;

			const contentContainer = iconPicker.querySelector(
				".icon-picker__content"
			);

			const page = packData.currentPageSearch || 1;
			const start = (page - 1) * packData.limit;
			const end = Math.min(start + packData.limit, packData.searchResults.length);
			const iconsToDisplay = packData.searchResults.slice(start, end);

			appendIconElements(contentContainer, iconsToDisplay);
		}

		function loadIconsForPack(packPrefix, styleName = null) {
			const contentContainer = iconPicker.querySelector(
				".icon-picker__content"
			);
			contentContainer.innerHTML = createSkeletonLoader();

			const cacheKey = getCacheKey(packPrefix, styleName);

			if (
				iconPacksData[cacheKey] &&
				iconPacksData[cacheKey].icons.length > 0
			) {
				renderIconsForPack(packPrefix, null, styleName);
				return;
			}

			if (iconCache[cacheKey]) {
				iconPacksData[cacheKey] = iconCache[cacheKey];
				renderIconsForPack(packPrefix, null, styleName);
				return;
			}

			iconPacksData[cacheKey] = {
				currentPage: 1,
				totalPages: 1,
				icons: [],
				searchResults: [],
				limit: 150,
				hasPage: { 1: false },
			};

			let url = `admin/api/icons/batch-render?prefix=${packPrefix}&limit=${iconPacksData[cacheKey].limit}&page=1`;
			if (styleName) {
				url += `&category=${styleName}`;
			}

			fetch(u(url))
				.then((response) => response.json())
				.then((data) => {
					iconPacksData[cacheKey].icons = data.icons.map((icon) => ({
						path: icon.path,
						svg: icon.svg,
						displayName: icon.displayName,
					}));

					iconPacksData[cacheKey].totalPages = data.totalPages;
					iconPacksData[cacheKey].total = data.total;
					iconPacksData[cacheKey].hasPage[1] = true;

					iconCache[cacheKey] = { ...iconPacksData[cacheKey] };
					saveIconCache();

					renderIconsForPack(packPrefix, null, styleName);
				})
				.catch((error) => {
					console.error(
						`Ошибка при загрузке иконок для пакета ${packPrefix}:`,
						error
					);
					contentContainer.innerHTML =
						'<div class="icon-picker__error">Не удалось загрузить иконки</div>';
				});
		}

		function renderIconsForPack(
			packPrefix,
			searchText = null,
			styleName = null
		) {
			const cacheKey = getCacheKey(packPrefix, styleName);
			const packData = iconPacksData[cacheKey];

			if (!packData) return;

			const contentContainer = iconPicker.querySelector(
				".icon-picker__content"
			);

			contentContainer.innerHTML = "";
			contentContainer.scrollTop = 0;

			if (searchText && searchText.length >= 2) {
				searchIcons(packPrefix, searchText, styleName);
				return;
			}

			if (packData.searching) {
				packData.searching = false;
				packData.searchQuery = null;
			}

			// Reset to page 1 for fresh render
			packData.currentPage = 1;

			if (!packData.hasPage[1]) {
				loadPageForPack(packPrefix, 1, styleName);
				return;
			}

			const iconsToDisplay = packData.icons.slice(0, packData.limit);

			if (iconsToDisplay.length === 0) {
				contentContainer.innerHTML =
					'<div class="icon-picker__empty">Ничего не найдено</div>';
				return;
			}

			appendIconElements(contentContainer, iconsToDisplay);
		}

		function appendIconsForPage(packPrefix, page, styleName = null) {
			const cacheKey = getCacheKey(packPrefix, styleName);
			const packData = iconPacksData[cacheKey];
			if (!packData) return;

			const contentContainer = iconPicker.querySelector(
				".icon-picker__content"
			);

			const start = (page - 1) * packData.limit;
			const iconsToDisplay = packData.icons.slice(start, start + packData.limit).filter(Boolean);

			appendIconElements(contentContainer, iconsToDisplay);
		}

		function escapeAttr(str) {
			return str.replace(/&/g, "&amp;").replace(/"/g, "&quot;").replace(/</g, "&lt;");
		}

		function appendIconElements(container, icons) {
			const currentValue = iconPicker.currentInput ? iconPicker.currentInput.value : "";
			const html = icons.map((icon) => {
				const cls = "icon-picker__icon" + (icon.path === currentValue ? " active" : "");
				return `<div class="${cls}" data-icon-path="${escapeAttr(icon.path)}" title="${escapeAttr(icon.displayName)}">${icon.svg}</div>`;
			}).join("");

			const temp = document.createElement("div");
			temp.innerHTML = html;
			const fragment = document.createDocumentFragment();
			while (temp.firstChild) {
				fragment.appendChild(temp.firstChild);
			}
			container.appendChild(fragment);
		}

		function loadPageForPack(packPrefix, page, styleName = null) {
			const contentContainer = iconPicker.querySelector(
				".icon-picker__content"
			);
			const cacheKey = getCacheKey(packPrefix, styleName);
			const packData = iconPacksData[cacheKey];
			if (!packData) return;

			if (!packData.hasPage) {
				packData.hasPage = {};
			}

			// If first page and container is empty, show skeleton
			if (page === 1 && contentContainer.children.length === 0) {
				contentContainer.innerHTML = createSkeletonLoader();
			}

			if (
				iconCache[cacheKey] &&
				iconCache[cacheKey].hasPage &&
				iconCache[cacheKey].hasPage[page]
			) {
				iconPacksData[cacheKey] = { ...iconCache[cacheKey] };
				packData.hasPage[page] = true;
				if (page === 1) {
					renderIconsForPack(packPrefix, null, styleName);
				} else {
					appendIconsForPage(packPrefix, page, styleName);
				}
				return;
			}

			let url = `admin/api/icons/batch-render?prefix=${packPrefix}&limit=${packData.limit}&page=${page}`;
			if (styleName) {
				url += `&category=${styleName}`;
			}

			fetch(u(url))
				.then((response) => response.json())
				.then((data) => {
					const newIcons = data.icons.map((icon) => ({
						path: icon.path,
						svg: icon.svg,
						displayName: icon.displayName,
					}));

					packData.hasPage[page] = true;

					const start = (page - 1) * packData.limit;
					const end = start + packData.limit;

					if (packData.icons.length < end) {
						packData.icons.length = end;
					}

					for (let i = 0; i < newIcons.length; i++) {
						packData.icons[start + i] = newIcons[i];
					}

					iconCache[cacheKey] = { ...packData };
					saveIconCache();

					if (page === 1) {
						renderIconsForPack(packPrefix, null, styleName);
					} else {
						appendIconsForPage(packPrefix, page, styleName);
					}
				})
				.catch((error) => {
					console.error(
						`Ошибка при загрузке страницы ${page} иконок для пакета ${packPrefix}:`,
						error
					);
				});
		}

		function loadAndAppendPage(packPrefix, page, styleName = null) {
			const cacheKey = getCacheKey(packPrefix, styleName);
			const packData = iconPacksData[cacheKey];
			if (!packData) return Promise.resolve();

			if (!packData.hasPage) {
				packData.hasPage = {};
			}

			if (packData.hasPage[page]) {
				appendIconsForPage(packPrefix, page, styleName);
				return Promise.resolve();
			}

			let url = `admin/api/icons/batch-render?prefix=${packPrefix}&limit=${packData.limit}&page=${page}`;
			if (styleName) {
				url += `&category=${styleName}`;
			}

			return fetch(u(url))
				.then((response) => response.json())
				.then((data) => {
					const newIcons = data.icons.map((icon) => ({
						path: icon.path,
						svg: icon.svg,
						displayName: icon.displayName,
					}));

					packData.hasPage[page] = true;

					const start = (page - 1) * packData.limit;
					const end = start + packData.limit;

					if (packData.icons.length < end) {
						packData.icons.length = end;
					}

					for (let i = 0; i < newIcons.length; i++) {
						packData.icons[start + i] = newIcons[i];
					}

					iconCache[cacheKey] = { ...packData };
					saveIconCache();

					appendIconsForPage(packPrefix, page, styleName);
				})
				.catch((error) => {
					console.error(
						`Ошибка при загрузке страницы ${page}:`,
						error
					);
				});
		}

		function selectIcon(iconPath) {
			if (!iconPicker.currentInput) return;

			iconPicker.currentInput.value = iconPath;
			updateSelectedIcon(iconPath);
			hideIconPicker();

			iconPicker.currentInput.dispatchEvent(
				new Event("input", { bubbles: true })
			);
			iconPicker.currentInput.dispatchEvent(
				new Event("change", { bubbles: true })
			);
			iconPicker.currentInput.focus();
		}

		function updateSelectedIcon(iconPath) {
			const container = iconPicker.currentInput.closest(
				".icon-input-container"
			);
			if (!container) return;

			const preview = container.querySelector(".icon-input-preview");
			if (!preview) return;

			if (!iconPath) {
				preview.innerHTML = "";
				return;
			}

			for (const cacheKey in iconPacksData) {
				const packData = iconPacksData[cacheKey];
				const icon = packData.icons.find((i) => i.path === iconPath);

				if (icon) {
					preview.innerHTML = icon.svg;
					return;
				}

				if (packData.searchResults) {
					const searchIcon = packData.searchResults.find(
						(i) => i.path === iconPath
					);
					if (searchIcon) {
						preview.innerHTML = searchIcon.svg;
						return;
					}
				}
			}

			fetch(
				u(
					`/admin/api/icons/render?path=${encodeURIComponent(
						iconPath
					)}`
				)
			)
				.then((response) => response.text())
				.then((svgContent) => {
					preview.innerHTML = svgContent;
				})
				.catch(() => {
					preview.innerHTML = "";
				});
		}

		function reapplySearch() {
			const searchInput = iconPicker.querySelector(
				".icon-picker__search-input"
			);
			const searchText = searchInput ? searchInput.value.trim().toLowerCase() : "";

			const activePackTab = iconPicker.querySelector(".icon-picker__tab.active");
			const activeStyle = iconPicker.querySelector(".icon-picker__style.active");
			const styleName = activeStyle ? activeStyle.dataset.style : null;
			let packName = null;

			if (activePackTab) {
				packName = activePackTab.dataset.pack;
			} else {
				const firstCategoryElem = iconPicker.querySelector(".icon-picker__category.active");
				if (firstCategoryElem) {
					const categoryKey = firstCategoryElem.dataset.category;
					const packsInCategory = categorizedPacks[categoryKey];
					if (packsInCategory && packsInCategory.length > 0) {
						packName = packsInCategory[0].prefix;
					}
				}
			}

			if (!packName) return;

			if (searchText.length >= 2) {
				searchIcons(packName, searchText, styleName || undefined);
			} else {
				loadIconsForPack(packName, styleName || undefined);
			}
		}

		function createSkeletonLoader() {
			const count = 24;
			let html = '<div class="icon-picker__skeleton">';

			for (let i = 0; i < count; i++) {
				html += '<div class="icon-picker__skeleton-item"></div>';
			}

			html += "</div>";
			return html;
		}
	}

	window.togglePassword = function (event) {
		const button = event.currentTarget;
		const input = button.previousElementSibling;
		const iconEye = button.querySelector(".icon-eye");
		const iconEyeSlash = button.querySelector(".icon-eye-slash");

		if (input.type === "password") {
			input.type = "text";
			iconEye.style.display = "none";
			iconEyeSlash.style.display = "block";
		} else {
			input.type = "password";
			iconEye.style.display = "block";
			iconEyeSlash.style.display = "none";
		}
	};
});

function debounce(func, wait) {
	let timeout;
	return function () {
		const context = this;
		const args = arguments;
		clearTimeout(timeout);
		timeout = setTimeout(() => {
			func.apply(context, args);
		}, wait);
	};
}
