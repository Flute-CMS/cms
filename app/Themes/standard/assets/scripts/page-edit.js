class PageEditorConfig {
    constructor(options = {}) {
        this.selectors = {
            editBtn: "#page-change-button",
            cancelBtn: "#page-change-cancel",
            widgetsSidebar: ".page-edit-widgets",
            pageEditBtn: "#page-edit-button",
            navbar: ".page-edit-nav",
            widgetGrid: "#widget-grid",
            searchInput: "#page-edit-widgets-search",
            resetBtn: "#page-edit-reset",
            undoBtn: "#page-edit-undo",
            redoBtn: "#page-edit-redo",
            saveBtn: "#page-edit-save",
            autoPositionBtn: "#page-edit-auto-position",
            heightModeToggle: "#height-mode-toggle",
            seoBtn: "#page-change-seo",
            ...options.selectors,
        };

        this.icons = {
            settings:
                document.getElementById("settings-widget-icon")?.innerHTML ||
                "⚙️",
            delete:
                document.getElementById("delete-widget-icon")?.innerHTML ||
                "🗑️",
            refresh:
                document.getElementById("refresh-widget-icon")?.innerHTML ||
                "🔄",
            ...options.icons,
        };

        this.gridOptions = {
            margin: 10,
            acceptWidgets: true,
            sizeToContent: true,
            disableDrag: false,
            disableResize: false,
            animate: true,
            ...options.gridOptions,
        };

        this.shortcuts = {
            undo: { key: "z", ctrl: true },
            redo: { key: "y", ctrl: true },
            save: { key: "s", ctrl: true },
            escape: { key: "Escape" },
            ...options.shortcuts,
        };

        this.translations = {
            unsavedChanges: "You have unsaved changes. Leave without saving?",
            resetConfirm: "Reset all changes?",
            errorLoading: "Error loading widget",
            errorSaving: "Error saving layout: ",
            finish: translate("def.finish"),
            more: translate("def.more"),
            settings: translate("def.widget_settings"),
            delete: translate("def.delete_widget"),
            refresh: translate("def.refresh_widget"),
            ...options.translations,
        };

        this.widgetButtons = {
            ...options.widgetButtons,
        };
    }
}
class OnboardingManager {
    constructor(editor) {
        this.editor = editor;
        this.config = editor.config;
        this.onboardingShownKey = "page-edit-onboarding-shown";
        this.container = document.getElementById("pageEditOnboarding");
        this.slidesContainer = document.getElementById("onboardingSlides");
        this.indicatorsContainer = document.getElementById(
            "onboardingIndicators"
        );
        this.nextBtn = document.getElementById("onboardingNextBtn");
        this.currentSlideIndex = 0;
    }

    initialize() {
        if (!this.container || !this.slidesContainer) return;
        if (localStorage.getItem(this.onboardingShownKey)) return;

        this.slides = this.slidesContainer.querySelectorAll(
            ".page-edit-onboarding-slide"
        );

        if (this.slides.length === 0) {
            this.container.style.display = "none";
            return;
        }

        this.setupIndicators();
        this.setupEventListeners();
        this.show();
    }

    setupIndicators() {
        if (this.indicatorsContainer) {
            this.indicatorsContainer.innerHTML = "";

            this.slides.forEach((_, index) => {
                const indicator = document.createElement("div");
                indicator.classList.add("indicator");
                if (index === 0) indicator.classList.add("active");
                indicator.dataset.slideIndex = index;
                indicator.addEventListener("click", () =>
                    this.goToSlide(index)
                );
                this.indicatorsContainer.appendChild(indicator);
            });
        }
    }

    setupEventListeners() {
        this.nextBtn?.addEventListener("click", () => {
            if (this.currentSlideIndex < this.slides.length - 1) {
                this.currentSlideIndex++;
                this.update();
            } else {
                this.finish();
            }
        });
    }

    update() {
        this.slides.forEach((slide, index) => {
            slide.classList.toggle("active", index === this.currentSlideIndex);
        });

        const indicators =
            this.indicatorsContainer?.querySelectorAll(".indicator");
        if (indicators) {
            indicators.forEach((indicator, index) => {
                indicator.classList.toggle(
                    "active",
                    index === this.currentSlideIndex
                );
            });
        }

        if (this.nextBtn) {
            const isLastSlide =
                this.currentSlideIndex === this.slides.length - 1;
            this.nextBtn.innerHTML = isLastSlide
                ? translate("page.onboarding.finish")
                : translate("page.onboarding.next");
        }
    }

    goToSlide(index) {
        if (index < 0 || index >= this.slides.length) return;
        this.currentSlideIndex = index;
        this.update();
    }

    show() {
        this.container.style.display = "flex";
        setTimeout(() => {
            this.container.classList.add("active");
            this.update();
        }, 50);
    }

    finish() {
        this.container.classList.remove("active");
        setTimeout(() => {
            this.container.style.display = "none";
        }, 300);
        localStorage.setItem(this.onboardingShownKey, "true");
    }
}
class HistoryManager {
    constructor(editor) {
        this.editor = editor;
        this.states = [];
        this.currentIndex = -1;
        this.isProcessing = false;
        this.maxStates = 50;
    }

    createSnapshot() {
        const items = Array.from(
            document.querySelectorAll(".grid-stack .grid-stack-item")
        ).map((el) => {
            const node = el.gridstackNode;
            const toolbar = el.querySelector(".widget-toolbar");

            return {
                id: el.getAttribute("data-widget-id"),
                widgetName: el.getAttribute("data-widget-name"),
                settings: el.dataset.widgetSettings,
                content: el.querySelector(".grid-stack-item-content")
                    ?.innerHTML,
                buttons: toolbar
                    ? this.editor.widgetButtonsCache[
                          el.getAttribute("data-widget-name")
                      ]
                    : [],
                position: {
                    x: node.x,
                    y: node.y,
                    w: node.w,
                    h: node.h,
                },
            };
        });

        return {
            items,
            timestamp: Date.now(),
        };
    }

    applySnapshot(snapshot) {
        if (!snapshot || !snapshot.items) return;

        this.isProcessing = true;

        this.editor.grid.removeAll();

        snapshot.items.forEach((item) => {
            const div = document.createElement("div");
            div.classList.add("grid-stack-item");

            if (item.id) div.setAttribute("data-widget-id", item.id);
            div.setAttribute("data-widget-name", item.widgetName || "");
            div.dataset.widgetSettings = item.settings;

            Object.entries(item.position).forEach(([key, value]) => {
                div.setAttribute(`gs-${key}`, value);
            });

            const content = document.createElement("div");
            content.classList.add("grid-stack-item-content");
            content.innerHTML = item.content;
            content.style.pointerEvents = "auto";
            div.appendChild(content);

            const widget = this.editor.grid.makeWidget(div);

            this.editor.grid.update(widget, item.position);

            if (item.buttons) {
                this.editor.addToolbar(div, item.buttons);
            }
        });

        this.isProcessing = false;
    }

    push() {
        if (this.isProcessing) return;

        if (this.currentIndex < this.states.length - 1) {
            this.states = this.states.slice(0, this.currentIndex + 1);
        }

        this.states.push(this.createSnapshot());
        this.currentIndex = this.states.length - 1;

        if (this.states.length > this.maxStates) {
            this.states.shift();
            this.currentIndex--;
        }

        this.editor.updateUndoRedoButtons();
    }

    undo() {
        if (this.currentIndex > 0) {
            this.currentIndex--;
            this.applySnapshot(this.states[this.currentIndex]);
            this.editor.updateUndoRedoButtons();
            return true;
        }
        return false;
    }

    redo() {
        if (this.currentIndex < this.states.length - 1) {
            this.currentIndex++;
            this.applySnapshot(this.states[this.currentIndex]);
            this.editor.updateUndoRedoButtons();
            return true;
        }
        return false;
    }

    canUndo() {
        return this.currentIndex > 0;
    }

    canRedo() {
        return this.currentIndex < this.states.length - 1;
    }

    clear() {
        this.states = [];
        this.currentIndex = -1;
        this.editor.updateUndoRedoButtons();
    }
}
class PageEditor {
    constructor(options = {}) {
        this.config = new PageEditorConfig(options);
        this.history = new HistoryManager(this);
        this.grid = null;
        this.hasUnsavedChanges = false;
        this.isProcessing = false;
        this.animationDuration = 300;
        this.autoSave = false;
        this.autoSaveInterval = null;
        this.heightMode = "auto"; // 'auto' or 'manual'
        this._docListenersAttached = false;
        this._htmxListenersAttached = false;
        this._searchDebounce = null;

        this.elements = {};
        this.initializeElements();

        this.onboarding = new OnboardingManager(this);
        this.setupEventListeners();
        this.setupHeightModeToggle();

        document.addEventListener("htmx:afterSwap", (e) => {
            if (e.detail.target?.id === "main") {
                this.setupEventListeners();
            }
        });

        this.isEditorFocused = false;
        this.pendingOperations = 0;

        this.baseWidgetButtons = {
            settings: {
                icon: this.config.icons.settings,
                tooltipKey: "def.widget_settings",
                order: 20,
                onClick: (widgetEl, editor) => {
                    editor.openWidgetSettings(widgetEl);
                },
                shouldShow: (widgetEl) => {
                    return (
                        widgetEl.hasAttribute("data-has-settings") &&
                        widgetEl.getAttribute("data-has-settings") === "true"
                    );
                },
            },
            refresh: {
                icon: this.config.icons.refresh,
                tooltipKey: "def.refresh_widget",
                order: 10,
                onClick: (widgetEl, editor) => {
                    editor.refreshWidget(widgetEl);
                },
                shouldShow: (widgetEl) => {
                    const widgetName =
                        widgetEl.getAttribute("data-widget-name");
                    return widgetName !== "Content";
                },
            },
            delete: {
                icon: this.config.icons.delete,
                tooltipKey: "def.delete_widget",
                order: 100,
                onClick: (widgetEl, editor) => {
                    // Prevent deletion of Content widget
                    const widgetName =
                        widgetEl.getAttribute("data-widget-name");
                    if (widgetName === "Content") {
                        return;
                    }
                    editor.grid.removeWidget(widgetEl);
                },
                shouldShow: (widgetEl) => {
                    const widgetName =
                        widgetEl.getAttribute("data-widget-name");
                    return widgetName !== "Content";
                },
            },
        };

        this.widgetButtonsCache = {};

        this.activeCategory = null;
        this.setupCategoryHandlers();

        this.logError = (context, error) => {
            console.error(`PageEditor [${context}]:`, error);
        };

        this.attemptRecoveryFromLocalStorage();
    }

    setupHeightModeToggle() {
        if (!this.elements.heightModeToggle) return;

        const savedMode = localStorage.getItem("pageEditHeightMode") || "auto";
        this.heightMode = savedMode;
        this.updateHeightModeButton();

        this.elements.heightModeToggle.addEventListener("click", (e) => {
            this.heightMode = this.heightMode === "auto" ? "manual" : "auto";
            localStorage.setItem("pageEditHeightMode", this.heightMode);
            this.updateHeightModeButton();
            this.applyHeightModeToGrid();
        });
    }

    updateHeightModeButton() {
        if (!this.elements.heightModeToggle) return;

        if (this.heightMode === "auto") {
            this.elements.heightModeToggle.classList.add("active");
            this.elements.heightModeToggle.classList.remove("manual");
            // Update tooltip
            const autoTooltip =
                this.elements.heightModeToggle.getAttribute(
                    "data-tooltip-auto"
                );
            this.elements.heightModeToggle.setAttribute(
                "data-tooltip",
                autoTooltip
            );
        } else {
            this.elements.heightModeToggle.classList.remove("active");
            this.elements.heightModeToggle.classList.add("manual");
            // Update tooltip
            const manualTooltip = this.elements.heightModeToggle.getAttribute(
                "data-tooltip-manual"
            );
            this.elements.heightModeToggle.setAttribute(
                "data-tooltip",
                manualTooltip
            );
        }
    }

    applyHeightModeToGrid() {
        if (!this.grid) return;

        if (this.heightMode === "auto") {
            this.grid.opts.sizeToContent = true;
            this.grid.opts.cellHeight = 100;
        } else {
            this.grid.opts.sizeToContent = false;
            this.grid.opts.cellHeight = 100;
        }

        const widgets =
            this.elements.widgetGrid.querySelectorAll(".grid-stack-item");
        widgets.forEach((widget) => {
            const content = widget.querySelector(".grid-stack-item-content");
            if (content) {
                content.style.overflow =
                    this.heightMode === "auto" ? "visible" : "auto";
            }

            this.grid.resizable(widget, true);
            if (this.heightMode === "auto") {
                setTimeout(() => this.resizeWidgetToContentSafe(widget), 120);
            } else {
                this.forceContentReflow(widget);
            }
        });
    }

    autoFitWidgetHeights() {
        if (!this.grid || this.heightMode !== "auto") return;

        const widgets =
            this.elements.widgetGrid.querySelectorAll(".grid-stack-item");
        widgets.forEach((widget) => {
            const content = widget.querySelector(".grid-stack-item-content");
            if (content) {
                let cellPx = parseFloat(this.grid.cellHeight() || 60);
                if (isNaN(cellPx) || cellPx <= 0) cellPx = 60;
                const contentHeight = content.scrollHeight;
                const gridHeight = Math.max(
                    2,
                    Math.ceil(contentHeight / cellPx)
                );

                this.grid.update(widget, { h: gridHeight });
            }
        });
    }

    /**
     * Dispatch custom widget events
     */
    dispatchWidgetEvent(eventName, detail = {}) {
        try {
            const event = new CustomEvent(eventName, {
                detail,
                bubbles: true,
                cancelable: true,
            });
            document.dispatchEvent(event);
        } catch (err) {
            this.logError(`dispatchWidgetEvent ${eventName}`, err);
        }
    }

    /**
     * Get current page path
     */
    getCurrentPath() {
        return window.location.pathname || "/";
    }

    /**
     * Get localStorage key for current path
     */
    getLocalStorageKey() {
        return `page-layout-${this.getCurrentPath()}`;
    }

    initializeElements() {
        try {
            Object.entries(this.config.selectors).forEach(([key, selector]) => {
                this.elements[key] = document.querySelector(selector);
            });

            // Add SEO button element
            this.elements.seoBtn = document.querySelector("#page-change-seo");
        } catch (err) {
            console.error("Failed to initialize elements:", err);
        }
    }

    setupEventListeners() {
        try {
            const bindOnce = (el, type, handler, key = "default") => {
                if (!el) return;
                el._pe = el._pe || {};
                const mark = `${type}:${key}`;
                if (el._pe[mark]) return;
                el.addEventListener(type, handler);
                el._pe[mark] = true;
            };

            bindOnce(this.elements.editBtn, "click", () => this.enable());
            bindOnce(this.elements.cancelBtn, "click", () => {
                this.resetActiveCategory();
                this.disable();
            });
            bindOnce(this.elements.resetBtn, "click", () => this.resetLayout());
            bindOnce(this.elements.undoBtn, "click", () => this.history.undo());
            bindOnce(this.elements.redoBtn, "click", () => this.history.redo());
            bindOnce(this.elements.saveBtn, "click", () => this.saveLayout());

            // Auto-position button
            bindOnce(this.elements.autoPositionBtn, "click", () => {
                this.autoPositionGrid();
            });

            const containerWidthToggle = document.getElementById(
                "container-width-checkbox"
            );
            if (containerWidthToggle) {
                const savedContainerWidth =
                    localStorage.getItem("container-width-mode") || "container";
                const isFullWidth = savedContainerWidth === "fullwidth";
                containerWidthToggle.checked = isFullWidth;
                this.applyContainerWidth(isFullWidth);

                bindOnce(containerWidthToggle, "change", (e) => {
                    const isFullWidth = e.target.checked;
                    this.applyContainerWidth(isFullWidth);
                    const mode = isFullWidth ? "fullwidth" : "container";
                    localStorage.setItem("container-width-mode", mode);
                }, "container-width");
            }

            bindOnce(this.elements.seoBtn, "click", () =>
                app.dropdowns.closeAllDropdowns()
            );

            if (this.elements.searchInput) {
                bindOnce(
                    this.elements.searchInput,
                    "input",
                    (e) => {
                        clearTimeout(this._searchDebounce);
                        this._searchDebounce = setTimeout(
                            () => this.handleSearch(e),
                            180
                        );
                    },
                    "search-input"
                );
            }

            if (!this._docListenersAttached) {
                window.addEventListener("beforeunload", (e) =>
                    this.handleBeforeUnload(e)
                );
                document.addEventListener("keydown", (e) =>
                    this.handleKeyDown(e)
                );
                document.addEventListener("focusin", (e) => {
                    this.isEditorFocused = e.target.matches(
                        'input, textarea, [contenteditable="true"]'
                    );
                });
                document.addEventListener("focusout", () => {
                    this.isEditorFocused = false;
                });
                window.addEventListener("unhandledrejection", (evt) => {
                    this.handlePromiseRejection(evt.reason);
                });
                this._docListenersAttached = true;
            }

            this.setupHtmxListeners();
        } catch (err) {
            this.logError("setupEventListeners", err);
        }
    }

    setupHtmxListeners() {
        try {
            if (this._htmxListenersAttached) return;
            htmx.on("htmx:afterSwap", (evt) => this.handleHtmxAfterSwap(evt));
            htmx.on("htmx:beforeRequest", (evt) =>
                this.handleHtmxBeforeRequest(evt)
            );

            // Ensure CSRF header is attached to all HTMX requests
            htmx.on("htmx:configRequest", (evt) => {
                const token = getCsrfToken();
                if (token) evt.detail.headers["X-CSRF-Token"] = token;
            });

            htmx.on("htmx:responseError", (evt) => {
                this.logError("HTMX response error", {
                    status: evt.detail.xhr.status,
                    url: evt.detail.requestConfig?.url,
                    target: evt.detail.target?.id,
                });

                if (evt.detail.target?.id === "page-edit-dialog-content") {
                    evt.detail.target.innerHTML = `
                        <div class="alert alert-danger">
                            ${
                                this.config.translations.errorLoading ||
                                "Error loading content"
                            }
                        </div>
                    `;
                }
            });

            // Handle settings save response in dialog
            const editor = this;
            htmx.on("htmx:afterRequest", (evt) => {
                const elt = evt.detail.elt;
                if (elt?.id === "widget-settings-save-btn") {
                    let json;
                    try {
                        json = JSON.parse(evt.detail.xhr.response);
                    } catch {
                        // If server returned HTML (validation errors), render it into the sidebar
                        const sidebarContent = document.getElementById(
                            "page-edit-dialog-content"
                        );
                        if (sidebarContent && evt.detail.xhr?.response) {
                            sidebarContent.innerHTML = evt.detail.xhr.response;
                            try { htmx.process(sidebarContent); } catch {}
                            if (window.FluteSelect) {
                                window.FluteSelect.init();
                            }
                        }
                        return;
                    }
                    if (
                        json.success &&
                        json.html &&
                        json.settings &&
                        window.currentEditedWidgetEl
                    ) {
                        // Update widget HTML content with rendered result
						const content = window.currentEditedWidgetEl.querySelector(
							".grid-stack-item-content"
						);
						if (content) {
							content.innerHTML = json.html;
						}
                        const sidebarContent = document.getElementById(
                            "page-edit-dialog-content"
                        );
                        window.currentEditedWidgetEl.dataset.widgetSettings =
                            JSON.stringify(json.settings);
                        editor.autoResize(window.currentEditedWidgetEl);
                        editor.history.push();
                        editor.saveToLocalStorage();
                        if (editor.rightSidebarDialog)
                            editor.rightSidebarDialog.hide();
                    }
                }
            });
            this._htmxListenersAttached = true;
        } catch (err) {
            this.logError("setupHtmxListeners", err);
        }
    }

    attemptRecoveryFromLocalStorage() {
        try {
            const savedLayout = localStorage.getItem(this.getLocalStorageKey());
            if (
                savedLayout &&
                document.body.classList.contains("page-edit-mode")
            ) {
                console.info(
                    "Attempting to recover layout from local storage for path:",
                    this.getCurrentPath()
                );
            }

            if (
                savedLayout &&
                document.body.classList.contains("page-edit-mode")
            ) {
                console.info(
                    "Attempting to recover layout from localStorage for path:",
                    this.getCurrentPath()
                );
                this.loadFromLocalStorage();
                this.hasUnsavedChanges = true;
                this.updateSaveButtonState();
            }
        } catch (err) {
            this.logError("attemptRecoveryFromLocalStorage", err);
        }
    }

    resetActiveCategory() {
        document
            .querySelectorAll(".widget-category-header.active")
            .forEach((header) => {
                header.classList.remove("active");
                if (header.nextElementSibling) {
                    header.nextElementSibling.classList.remove("active");
                }
            });
        this.activeCategory = null;
    }

    enable() {
        if (this.isProcessing) return;
        this.isProcessing = true;

        try {
            this.currentPath = this.getCurrentPath();
            this.localStorageKey = this.getLocalStorageKey();

            this.onboarding.initialize();
            document.body.classList.add("page-edit-mode");

            this.elements.widgetsSidebar?.classList.add("active");
            this.elements.navbar?.classList.add("active");
            this.elements.pageEditBtn?.classList.add("hide");

            this.resetActiveCategory();

            const mainElement = document.getElementById("main");
            if (!mainElement) {
                throw new Error("Main element not found");
            }

            mainElement.innerHTML = `
                <div class="container">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="grid-stack" id="widget-grid" data-gs-animate="yes"></div>
                        </div>
                    </div>
                </div>
            `;

            this.initializeGrid();
            setTimeout(() => {
                this.elements.searchInput?.focus();
                this.isProcessing = false;
            }, this.animationDuration + 100);

            app.dropdowns.closeAllDropdowns();

            this.activeCategory = null;
            this.setupCategoryHandlers();

            this.updateHeightModeButton();
        } catch (err) {
            this.isProcessing = false;
            this.logError("enable", err);
            this.disable(true);
        }
    }

    disable(ignoreHtmx = false) {
        if (this.isProcessing) return;

        try {
            if (this.hasUnsavedChanges) {
                const confirmLeave = confirm(
                    this.config.translations.unsavedChanges
                );
                if (!confirmLeave) return;
            }

            this.isProcessing = true;

            document.body.classList.remove("page-edit-mode");
            this.elements.widgetsSidebar?.classList.remove("active");
            this.elements.navbar?.classList.remove("active");
            this.elements.pageEditBtn?.classList.remove("hide");

            this.resetActiveCategory();

            this.destroyGrid(ignoreHtmx);

            app.dropdowns.closeAllDropdowns();

            setTimeout(() => {
                this.isProcessing = false;
            }, this.animationDuration);
        } catch (err) {
            this.isProcessing = false;
            this.logError("disable", err);
        }
    }

    initializeGrid() {
        if (this.grid) return;

        try {
            this.elements.widgetGrid = document.getElementById("widget-grid");

            if (!this.elements.widgetGrid) {
                throw new Error("Widget grid element not found");
            }

            const gridOptions = {
                ...this.config.gridOptions,
                cellHeight: this.heightMode === "auto" ? "auto" : 100,
                sizeToContent: this.heightMode === "auto",
                float: false,
                column: 12,
                minRow: 1,
            };

            this.grid = GridStack.init(gridOptions, this.elements.widgetGrid);

            if (!this.grid) {
                throw new Error("Failed to initialize GridStack");
            }

            try {
                GridStack.setupDragIn(
                    '.page-edit-widgets-item:not([data-widget-name="Content"])',
                    {
                        helper: "clone",
                        scroll: true,
                        appendTo: "body",
                    }
                );
            } catch (dragErr) {
                this.logError("setupDragIn", dragErr);
            }

            if (typeof this.grid.cellWidth === "function") {
                this.grid.cellHeight(this.grid.cellWidth() / 2);
            }

            this.setupGridEvents();

            const savedLayout = this.loadFromLocalStorage();
            if (!savedLayout) {
                this.fetchLayoutFromServer();
            } else {
                // Проверяем, есть ли виджет Content после загрузки из localStorage
                setTimeout(() => {
                    const hasContentWidget =
                        this.elements.widgetGrid.querySelector(
                            '[data-widget-name="Content"]'
                        );
                    if (!hasContentWidget && this.getCurrentPath() !== "/") {
                        this.addContentWidget();
                    }
                }, 500);
            }

            this.applyHeightModeToGrid();
        } catch (err) {
            this.logError("initializeGrid", err);
            this.showErrorNotification(
                "Failed to initialize page editor. Please refresh the page."
            );
        }
    }

    setupGridEvents() {
        try {
            if (!this.grid) return;

            let changeTimeout;
            const handleChange = () => {
                clearTimeout(changeTimeout);
                changeTimeout = setTimeout(() => this.handleGridChange(), 100);
            };

            this.grid.on("added removed change", handleChange);
            this.grid.on("resizestop", handleChange);
            this.grid.on("dropped", (ev, prev, newW) => {
                try {
                    this.handleWidgetDrop(ev, prev, newW);
                } catch (err) {
                    this.logError("widgetDrop event", err);
                }
            });

            this.grid.on("removed", (ev, items) => {
                items.forEach((item) => {
                    if (
                        item.el &&
                        item.el.getAttribute("data-widget-name") === "Content"
                    ) {
                        setTimeout(() => {
                            this.addContentWidget();
                        }, 100);
                    }
                });
            });
        } catch (err) {
            this.logError("setupGridEvents", err);
        }
    }

    handleWidgetDrop(ev, prev, newW) {
        if (!newW || !newW.el || prev) return;

        try {
            const content = newW.el.querySelector(".grid-stack-item-content");
            if (!content) return;

            newW.el.style.transition = `all ${this.animationDuration}ms ease-in-out`;
            newW.el.classList.add("widget-dropping");

            setTimeout(() => {
                newW.el.classList.remove("widget-dropping");
                setTimeout(() => {
                    newW.el.style.transition = "";
                }, this.animationDuration);
            }, this.animationDuration);

            this.initializeWidget(newW.el, content);
        } catch (err) {
            this.logError("handleWidgetDrop", err);

            try {
                const content = newW.el.querySelector(
                    ".grid-stack-item-content"
                );
                if (content) {
                    content.innerHTML = `<div class="widget-error">${this.config.translations.errorLoading}</div>`;
                    content.style.pointerEvents = "auto";
                }
            } catch (recoveryErr) {
                if (this.grid && newW.el) {
                    this.grid.removeWidget(newW.el);
                }
            }
        }
    }

    async initializeWidget(widgetEl, content) {
        if (!widgetEl || !content) return;

        const widgetName = widgetEl.getAttribute("data-widget-name");
        if (!widgetName) return;

        this.pendingOperations++;
        content.style.pointerEvents = "none";
        content.innerHTML = this.createSkeleton();

        try {
            const [contentResponse, buttonsResponse] = await Promise.all([
                this.loadWidgetContent(widgetEl).catch((err) => {
                    this.logError(`loadWidgetContent for ${widgetName}`, err);
                    return {
                        html: `<div class="widget-error">${this.config.translations.errorLoading}</div>`,
                        settings: {},
                    };
                }),
                this.loadWidgetButtons(widgetName).catch((err) => {
                    this.logError(`loadWidgetButtons for ${widgetName}`, err);
                    return [];
                }),
            ]);

            if (!document.body.contains(widgetEl)) {
                this.pendingOperations--;
                return;
            }

            content.style.opacity = "0";
            content.innerHTML = contentResponse.html || "";

            widgetEl.dataset.widgetSettings = JSON.stringify(
                contentResponse.settings || {}
            );

            setTimeout(() => {
                content.style.transition = `opacity ${
                    this.animationDuration / 2
                }ms ease-in-out`;
                content.style.opacity = "1";
                content.style.pointerEvents = "auto";

                setTimeout(() => {
                    content.style.transition = "";
                }, this.animationDuration / 2);
            }, 50);

            this.addToolbar(widgetEl, buttonsResponse);
            this.autoResize(widgetEl);

            this.dispatchWidgetEvent("widgetInitialized", {
                widgetName,
                widgetElement: widgetEl,
                content: contentResponse,
            });

            // Auto-fit height if in auto mode
            if (this.heightMode === "auto") {
                setTimeout(() => {
                    this.autoFitSingleWidget(widgetEl, true);
                }, 100);
            }
        } catch (err) {
            this.logError(`initializeWidget ${widgetName}`, err);

            if (document.body.contains(widgetEl) && content) {
                content.innerHTML = `<div class="widget-error">${this.config.translations.errorLoading}</div>`;
                content.style.pointerEvents = "auto";
                this.addToolbar(widgetEl, []);
            }
        } finally {
            this.pendingOperations--;
        }
    }

    async loadWidgetContent(widgetEl) {
        const widgetName = widgetEl.getAttribute("data-widget-name");
        const settings = JSON.parse(widgetEl.dataset.widgetSettings || "{}");

        const res = await csrfFetch(u("api/pages/render-widget"), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                widget_name: widgetName,
                settings: settings,
            }),
        });

        if (!res.ok) throw new Error("Failed to load widget content");
        const result = await res.json();

        if (result.settings && widgetEl) {
            widgetEl.setAttribute(
                "data-has-settings",
                result.hasSettings !== undefined
                    ? result.hasSettings.toString()
                    : "false"
            );
        }

        return result;
    }

    async loadWidgetButtons(widgetName) {
        if (this.widgetButtonsCache[widgetName]) {
            return this.widgetButtonsCache[widgetName];
        }

        try {
            const res = await csrfFetch(u("api/pages/widgets/buttons"), {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    widget_name: widgetName,
                }),
            });

            if (!res.ok) return [];

            const buttons = await res.json();
            if (Array.isArray(buttons)) {
                this.widgetButtonsCache[widgetName] = buttons;
                return buttons;
            }
        } catch (err) {
            console.error("Failed to load widget buttons:", err);
        }

        return [];
    }

    addToolbar(widgetEl, customButtons = []) {
        if (!widgetEl || widgetEl.querySelector(".widget-toolbar")) return;

        try {
            const toolbar = document.createElement("div");
            toolbar.classList.add("widget-toolbar");

            Object.assign(toolbar.style, {
                opacity: "0",
                bottom: "-15px",
                transition: `opacity ${
                    this.animationDuration / 2
                }ms ease-in-out, transform ${
                    this.animationDuration / 2
                }ms ease-out`,
                position: "absolute",
                zIndex: "999",
                pointerEvents: "none",
            });

            const allButtons = [
                ...Object.entries(this.baseWidgetButtons).map(([key, btn]) => ({
                    ...btn,
                    key,
                    type: "base",
                })),
                ...customButtons.map((btn) => ({
                    ...btn,
                    order: btn.order || 50,
                    type: "custom",
                })),
            ];

            const filteredButtons = allButtons.filter((button) => {
                if (button.type === "base" && button.shouldShow) {
                    return button.shouldShow(widgetEl);
                }
                return true;
            });

            filteredButtons.sort((a, b) => a.order - b.order);

            const looksLikeKey = (val) =>
                typeof val === "string" && /^[a-z0-9_]+\.[a-z0-9_.]+$/i.test(val);

            const escapeAttr = (val) =>
                String(val ?? "")
                    .replace(/&/g, "&amp;")
                    .replace(/"/g, "&quot;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;");

            const toolbarHtml = filteredButtons
                .map((button) => {
                    if (button.type === "base") {
                        if (button.tooltipKey && typeof button.tooltipKey === "string") {
                            return `
                        <button class="widget-button widget-button-${button.key}" 
                                data-translate="${escapeAttr(button.tooltipKey)}"
                                data-translate-attribute="data-tooltip">
                            ${button.icon}
                        </button>
                    `;
                        }
                        const tt = button.tooltip ? escapeAttr(button.tooltip) : "";
                        return `
                        <button class="widget-button widget-button-${button.key}" 
                                ${tt ? `data-tooltip="${tt}"` : ""}>
                            ${button.icon}
                        </button>
                    `;
                    } else {
                        let attrs = `data-action="${escapeAttr(button.action)}"`;
                        if (button.tooltipKey && typeof button.tooltipKey === "string") {
                            attrs += ` data-translate="${escapeAttr(button.tooltipKey)}" data-translate-attribute="data-tooltip"`;
                        } else if (looksLikeKey(button.tooltip)) {
                            attrs += ` data-translate="${escapeAttr(button.tooltip)}" data-translate-attribute="data-tooltip"`;
                        } else if (button.tooltip) {
                            attrs += ` data-tooltip="${escapeAttr(button.tooltip)}"`;
                        }
                        return `
                        <button class="widget-button widget-button-custom" ${attrs}>
                            ${button.icon}
                        </button>
                    `;
                    }
                })
                .join("");

            toolbar.innerHTML = toolbarHtml;
            widgetEl.appendChild(toolbar);

            this.setupToolbarEvents(widgetEl, toolbar, filteredButtons);

            setTimeout(() => {
                toolbar.style.pointerEvents = "auto";
            }, 100);
        } catch (err) {
            this.logError("addToolbar", err);
        }
    }

    setupToolbarEvents(widgetEl, toolbar, allButtons) {
        if (!widgetEl || !toolbar) return;

        try {
            let hoverTimeout;

            widgetEl.addEventListener("mouseenter", () => {
                clearTimeout(hoverTimeout);
                hoverTimeout = setTimeout(() => {
                    toolbar.style.opacity = "1";
                }, 50);
            });

            widgetEl.addEventListener("mouseleave", () => {
                clearTimeout(hoverTimeout);
                toolbar.style.opacity = "0";
            });

            allButtons.forEach((button) => {
                let btn;
                if (button.type === "base") {
                    btn = toolbar.querySelector(`.widget-button-${button.key}`);
                } else {
                    btn = toolbar.querySelector(
                        `[data-action="${button.action}"]`
                    );
                }

                if (btn) {
                    btn.addEventListener("click", async (e) => {
                        e.stopPropagation();
                        e.preventDefault();

                        btn.style.transform = "scale(0.95)";
                        setTimeout(() => {
                            btn.style.transform = "scale(1)";
                        }, 100);

                        try {
                            if (button.type === "base") {
                                button.onClick(widgetEl, this);
                            } else {
                                await this.handleCustomButtonClick(
                                    widgetEl,
                                    button
                                );
                            }
                        } catch (err) {
                            this.logError(
                                `button click: ${
                                    button.type === "base"
                                        ? button.key
                                        : button.action
                                }`,
                                err
                            );
                        }
                    });
                }
            });
        } catch (err) {
            this.logError("setupToolbarEvents", err);
        }
    }

    async handleCustomButtonClick(widgetEl, button) {
        if (!widgetEl || !button || !button.action) return;

        const widgetName = widgetEl.getAttribute("data-widget-name");
        if (!widgetName) return;

        this.pendingOperations++;

        try {
            const res = await csrfFetch(u("api/pages/widgets/handle-action"), {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    widget_name: widgetName,
                    action: button.action,
                    widgetId: widgetEl.getAttribute("data-widget-id"),
                }),
            });

            if (!res.ok) {
                throw new Error(`Action failed with status: ${res.status}`);
            }

            const result = await res.json();

            if (!document.body.contains(widgetEl)) return;

            if (result.reload) {
                this.refreshWidget(widgetEl);
            }
        } catch (err) {
            this.logError(`handleCustomButtonClick: ${button.action}`, err);

            const content = widgetEl.querySelector(".grid-stack-item-content");
            if (content) {
                const errorEl = document.createElement("div");
                errorEl.className = "widget-action-error";
                errorEl.textContent = "Action failed";
                errorEl.style.position = "absolute";
                errorEl.style.top = "5px";
                errorEl.style.right = "5px";
                errorEl.style.background = "rgba(220, 53, 69, 0.8)";
                errorEl.style.color = "white";
                errorEl.style.padding = "3px 8px";
                errorEl.style.borderRadius = "3px";
                errorEl.style.fontSize = "12px";
                errorEl.style.opacity = "0";
                errorEl.style.transition = "opacity 0.3s ease-in-out";

                content.appendChild(errorEl);

                setTimeout(() => {
                    errorEl.style.opacity = "1";
                    setTimeout(() => {
                        errorEl.style.opacity = "0";
                        setTimeout(() => errorEl.remove(), 300);
                    }, 2000);
                }, 10);
            }
        } finally {
            this.pendingOperations--;
        }
    }

    autoResize(widgetEl) {
        if (!widgetEl || !this.grid) return;

        requestAnimationFrame(() => {
            try {
                if (!widgetEl.isConnected || !document.contains(widgetEl)) {
                    return;
                }

                const content = widgetEl.querySelector(
                    ".grid-stack-item-content"
                );
                if (!content) {
                    return;
                }

                if (typeof this.grid.resizeToContent === "function") {
                    widgetEl.style.transition = `height ${this.animationDuration}ms ease-in-out, 
                                                width ${this.animationDuration}ms ease-in-out`;

                    this.grid.resizeToContent(widgetEl);
                    this.handleGridChange();

                    setTimeout(() => {
                        widgetEl.style.transition = "";
                    }, this.animationDuration);
                }
            } catch (err) {
                this.logError("autoResize", err);
            }
        });
    }

    createSkeleton() {
        return `<div class="skeleton page-edit-skeleton" 
             style="animation: skeleton-loading 1.5s infinite ease-in-out;">
        </div>`;
    }

    getLayoutJson() {
        if (!this.grid) return [];

        try {
            const items = document.querySelectorAll(
                ".grid-stack .grid-stack-item"
            );
            if (!items || items.length === 0) return [];

            const sortedItems = Array.from(items).sort((a, b) => {
                const aNode = a.gridstackNode || {};
                const bNode = b.gridstackNode || {};
                if ((aNode.y || 0) === (bNode.y || 0)) {
                    return (aNode.x || 0) - (bNode.x || 0);
                }
                return (aNode.y || 0) - (bNode.y || 0);
            });

            return sortedItems
                .map((el, index) => {
                    try {
                        const node = el.gridstackNode || {};
                        let parsedSettings = {};

                        try {
                            const settingsStr = el.dataset.widgetSettings;
                            parsedSettings = settingsStr
                                ? JSON.parse(settingsStr)
                                : {};
                        } catch (jsonErr) {
                            this.logError(
                                "getLayoutJson parse settings",
                                jsonErr
                            );
                        }

                        return {
                            index,
                            id: el.getAttribute("data-widget-id") || null,
                            widgetName:
                                el.getAttribute("data-widget-name") || "",
                            settings: parsedSettings,
                            gridstack: {
                                h: node.h || 1,
                                w: node.w || 1,
                                x: node.x || 0,
                                y: node.y || 0,
                            },
                        };
                    } catch (itemErr) {
                        this.logError(`getLayoutJson item ${index}`, itemErr);
                        return {
                            index,
                            widgetName:
                                el.getAttribute("data-widget-name") ||
                                "unknown",
                            settings: {},
                            gridstack: { h: 1, w: 1, x: 0, y: 0 },
                        };
                    }
                })
                .filter(Boolean); // Remove any undefined items
        } catch (err) {
            this.logError("getLayoutJson", err);
            return [];
        }
    }

    async fetchLayoutFromServer() {
        if (this.isFetchingLayout) return;
        this.isFetchingLayout = true;

        let retryCount = 0;
        const maxRetries = 2;

        const tryFetch = async () => {
            try {
                const currentPath = window.location.pathname || "/";
                const res = await csrfFetch(
                    u(
                        `api/pages/get-layout?path=${encodeURIComponent(
                            currentPath
                        )}&_=${Date.now()}` // Cache busting
                    ),
                    {
                        method: "GET",
                        headers: { "Content-Type": "application/json" },
                        signal: AbortSignal.timeout(10000),
                    }
                );

                if (!res.ok) {
                    throw new Error(`Server responded with ${res.status}`);
                }

                const json = await res.json();

                if (!json || !json.layout) {
                    throw new Error("Invalid layout data received");
                }

                this.loadLayoutJson(json.layout);
                this.hasUnsavedChanges = false;
                this.updateSaveButtonState();
                return true;
            } catch (err) {
                this.logError(
                    `fetchLayoutFromServer (attempt ${retryCount + 1})`,
                    err
                );

                if (retryCount < maxRetries) {
                    retryCount++;
                    const backoff = Math.pow(2, retryCount - 1) * 1000;
                    await new Promise((resolve) =>
                        setTimeout(resolve, backoff)
                    );
                    return tryFetch();
                }

                this.showErrorNotification(
                    "Failed to load page layout. Using default layout."
                );
                return false;
            }
        };

        try {
            await tryFetch();
        } finally {
            this.isFetchingLayout = false;
        }
    }

    async loadButtonsBatch(widgetNames) {
        const uniqueNames = [...new Set(widgetNames)];
        const missingNames = uniqueNames.filter(
            (name) => !this.widgetButtonsCache[name]
        );

        if (missingNames.length === 0) {
            return;
        }

        try {
            const res = await csrfFetch(u("api/pages/widgets/buttons-batch"), {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ widget_names: missingNames }),
            });

            if (!res.ok) {
                throw new Error(`Failed to load batch buttons: ${res.status}`);
            }

            const batchButtons = await res.json();
            Object.entries(batchButtons).forEach(([name, buttons]) => {
                this.widgetButtonsCache[name] = buttons;
            });
        } catch (err) {
            this.logError("loadButtonsBatch", err);
        }
    }

    loadLayoutJson(data) {
        if (!this.grid || !Array.isArray(data)) return;

        try {
            this.grid.removeAll();
            const filtered = data;
            const widgetElements = [];
            const hasContentWidget = filtered.some(
                (item) => item.widgetName === "Content"
            );

            filtered.forEach((nd, idx) => {
                try {
                    const div = document.createElement("div");
                    div.classList.add("grid-stack-item");
                    div.setAttribute("data-widget-name", nd.widgetName || "");
                    if (nd.id) div.setAttribute("data-widget-id", nd.id);
                    div.dataset.widgetSettings = JSON.stringify(
                        nd.settings || {}
                    );
                    div.setAttribute("gs-w", nd.gridstack?.w || 12);
                    div.setAttribute("gs-h", nd.gridstack?.h || 4);
                    if (typeof nd.gridstack?.x === "number") {
                        div.setAttribute("gs-x", nd.gridstack.x);
                    }
                    if (typeof nd.gridstack?.y === "number") {
                        div.setAttribute("gs-y", nd.gridstack.y);
                    }
                    if (nd.widgetName === "Content")
                        div.setAttribute("gs-no-move", "true");
                    const content = document.createElement("div");
                    content.classList.add("grid-stack-item-content");
                    content.innerHTML = this.createSkeleton();
                    content.style.pointerEvents = "none";
                    div.appendChild(content);
                    const widgetEl = this.grid.makeWidget(div);
                    widgetElements.push({
                        el: widgetEl,
                        widgetName: nd.widgetName,
                        settings: nd.settings || {},
                    });
                } catch (err) {
                    this.logError("widget creation", err);
                }
            });

            if (widgetElements.length > 0) {
                const requestData = widgetElements.map((w) => ({
                    widget_name: w.widgetName,
                    settings: w.settings,
                }));

                csrfFetch(u("api/pages/render-widgets"), {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({ widgets: requestData }),
                })
                    .then((res) => {
                        if (!res.ok)
                            throw new Error(
                                `Failed to batch render widgets: ${res.status}`
                            );
                        return res.json();
                    })
                    .then(async (results) => {
                        // First, batch load buttons
                        const widgetNames = widgetElements.map(
                            (w) => w.widgetName
                        );
                        await this.loadButtonsBatch(widgetNames);

                        results.forEach((result, idx) => {
                            const { el } = widgetElements[idx];
                            if (!el || !document.contains(el)) return;

                            const content = el.querySelector(
                                ".grid-stack-item-content"
                            );
                            if (content) {
                                content.style.opacity = "0";
                                content.innerHTML =
                                    result.html ||
                                    '<div class="widget-error">Error loading widget</div>';
                                el.dataset.widgetSettings = JSON.stringify(
                                    result.settings || {}
                                );
                                el.setAttribute(
                                    "data-has-settings",
                                    result.hasSettings ? "true" : "false"
                                );

                                setTimeout(() => {
                                    content.style.transition = `opacity ${
                                        this.animationDuration / 2
                                    }ms ease-in-out`;
                                    content.style.opacity = "1";
                                    content.style.pointerEvents = "auto";
                                    setTimeout(() => {
                                        content.style.transition = "";
                                    }, this.animationDuration / 2);
                                }, 50);
                            }

                            // Use cached buttons
                            const buttons =
                                this.widgetButtonsCache[
                                    widgetElements[idx].widgetName
                                ] || [];
                            this.addToolbar(el, buttons);

                            this.autoResize(el);
                            this.dispatchWidgetEvent("widgetInitialized", {
                                widgetName: widgetElements[idx].widgetName,
                                widgetElement: el,
                                content: result,
                            });

                            if (this.heightMode === "auto") {
                                setTimeout(() => {
                                    this.autoFitSingleWidget(el, true);
                                }, 100);
                            }
                        });
                    })
                    .catch((err) => {
                        this.logError("batch render", err);
                        widgetElements.forEach(({ el }) => {
                            if (el && document.contains(el)) {
                                const content = el.querySelector(
                                    ".grid-stack-item-content"
                                );
                                if (content) {
                                    content.innerHTML =
                                        '<div class="widget-error">Failed to load widgets</div>';
                                    content.style.pointerEvents = "auto";
                                }
                            }
                        });
                    })
                    .finally(() => {
                        if (
                            !hasContentWidget &&
                            this.getCurrentPath() !== "/"
                        ) {
                            setTimeout(() => {
                                this.addContentWidget();
                            }, 600);
                        }
                    });
            } else {
                if (!hasContentWidget && this.getCurrentPath() !== "/") {
                    setTimeout(() => {
                        this.addContentWidget();
                    }, 600);
                }
            }
        } catch (err) {
            this.logError("loadLayoutJson", err);
        }
    }

    handleGridChange() {
        if (this.history.isProcessing) return;
        this.hasUnsavedChanges = true;
        this.updateSaveButtonState();
        this.history.push();
        this.saveToLocalStorage();
    }

    saveToLocalStorage() {
        try {
            const layoutJson = this.getLayoutJson();
            if (layoutJson.length === 0) return;

            localStorage.setItem(
                this.getLocalStorageKey(),
                JSON.stringify(layoutJson)
            );
        } catch (err) {
            this.logError("saveToLocalStorage", err);
            // Don't block the UI for localStorage errors
        }
    }

    async saveLayout() {
        if (!this.grid || this.isSaving) return;

        if (!this.hasUnsavedChanges) {
            this.disable();
            return;
        }

        this.isSaving = true;

        if (this.elements.saveBtn) {
            const btn = this.elements.saveBtn;
            btn.classList.add("saving");
            btn.disabled = true;
            if (!btn.getAttribute("data-original-text")) {
                btn.setAttribute("data-original-text", btn.innerHTML);
            }
            btn.innerHTML = `<span class="btn-spinner" aria-hidden="true"></span><span class="btn-text">${
                btn.getAttribute("data-saving-text") || translate("def.save")
            }</span>`;
            btn.setAttribute("aria-busy", "true");
        }

        try {
            const layoutData = this.getLayoutJson();
            // if (layoutData.length === 0) {
            //     throw new Error('No layout data to save');
            // }

            const csrfToken = getCsrfToken();
            if (!csrfToken) {
                throw new Error("CSRF token not found");
            }

            const currentPath = window.location.pathname || "/";
            const res = await csrfFetch(u("api/pages/save-layout"), {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    layout: layoutData,
                    path: currentPath,
                }),
                signal: AbortSignal.timeout(15000),
            });

            const json = await res.json();
            if (!res.ok) {
                throw new Error(
                    json?.error || `Failed to save layout (${res.status})`
                );
            }

            this.hasUnsavedChanges = false;
            this.updateSaveButtonState();

            // Сброс истории после успешного сохранения
            this.history.clear();
            this.updateUndoRedoButtons();

            localStorage.removeItem(this.getLocalStorageKey());

            notyf.success(translate("page.saved_successfully"));

            if (this.elements.saveBtn) {
                const btn = this.elements.saveBtn;
                btn.classList.remove("saving");
                btn.disabled = false;
                btn.innerHTML = btn.getAttribute("data-original-text") || "Save";
                btn.removeAttribute("aria-busy");
            }

            this.refreshPageContent();
        } catch (err) {
            this.logError("saveLayout", err);
            notyf.error(translate("page.error_saving") + err.message);

            if (this.elements.saveBtn) {
                const btn = this.elements.saveBtn;
                btn.classList.remove("saving");
                btn.disabled = false;
                btn.innerHTML =
                    btn.getAttribute("data-original-text") || "Save";
                btn.removeAttribute("aria-busy");
            }

            this.isSaving = false;
            return false;
        }

        this.disable();
        this.isSaving = false;
        return true;
    }

    refreshPageContent() {
        try {
            htmx.ajax("GET", window.location.href, "#main", {
                swap: "innerHTML transition:true",
                headers: {
                    "X-CSRF-Token":
                        document.querySelector('meta[name="csrf-token"]')
                            ?.content || "",
                },
            });
        } catch (err) {
            this.logError("refreshPageContent", err);
            window.location.reload();
        }
    }

    updateUndoRedoButtons() {
        if (this.elements.undoBtn) {
            this.elements.undoBtn.disabled = !this.history.canUndo();
        }
        if (this.elements.redoBtn) {
            this.elements.redoBtn.disabled = !this.history.canRedo();
        }
    }

    updateSaveButtonState() {
        if (this.elements.saveBtn) {
            this.elements.saveBtn.disabled = !this.hasUnsavedChanges;
        }
    }

    handleSearch(e) {
        const searchTerm = e.target.value.toLowerCase().trim();
        const categories = document.querySelectorAll(".widget-category");

        categories.forEach((category) => {
            const header = category.querySelector(".widget-category-header");
            const items = category.querySelectorAll(".page-edit-widgets-item");
            let hasVisibleItems = false;

            items.forEach((item) => {
                const text = item.textContent.toLowerCase();
                const isVisible = text.includes(searchTerm);
                item.style.display = isVisible ? "" : "none";
                if (isVisible) hasVisibleItems = true;
            });

            category.style.display = hasVisibleItems ? "" : "none";

            if (hasVisibleItems && searchTerm) {
                this.toggleCategory(header, true);
            } else if (!searchTerm) {
                this.toggleCategory(
                    header,
                    header.dataset.category === this.activeCategory
                );
            }
        });
    }

    handleBeforeUnload(e) {
        if (this.hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = "";
        }
    }

    handleKeyDown(e) {
        if (this.isEditorFocused) return;

        const { shortcuts } = this.config;

        for (const [action, shortcut] of Object.entries(shortcuts)) {
            const isCtrlPressed = shortcut.ctrl ? e.ctrlKey || e.metaKey : true;
            const isShiftPressed = shortcut.shift ? e.shiftKey : true;
            const isAltPressed = shortcut.alt ? e.altKey : true;
            const isKeyPressed =
                e.key.toLowerCase() === shortcut.key.toLowerCase();

            if (
                isCtrlPressed &&
                isShiftPressed &&
                isAltPressed &&
                isKeyPressed
            ) {
                e.preventDefault();

                switch (action) {
                    case "undo":
                        this.history.undo();
                        break;
                    case "redo":
                        this.history.redo();
                        break;
                    case "save":
                        if (this.hasUnsavedChanges) {
                            this.saveLayout();
                        }
                        break;
                    case "escape":
                        if (
                            document.body.classList.contains("page-edit-mode")
                        ) {
                            const confirmLeave = this.hasUnsavedChanges
                                ? confirm(
                                      this.config.translations.unsavedChanges
                                  )
                                : true;

                            if (confirmLeave) {
                                this.disable();
                            }
                        }
                        break;
                }

                return;
            }
        }
    }

    handleHtmxAfterSwap(evt) {
        if (
            evt.detail.requestConfig?.url === window.location.href &&
            evt.detail.target?.id === "main"
        ) {
            this.currentPath = this.getCurrentPath();
            this.localStorageKey = this.getLocalStorageKey();

            this.elements.widgetGrid = document.getElementById("widget-grid");
            if (
                document.body.classList.contains("page-edit-mode") &&
                this.elements.widgetGrid &&
                !this.grid
            ) {
                this.initializeGrid();
            }
        }

        try {
            if (evt.detail.target?.id === "page-edit-dialog-content") {
                const response = evt.detail.xhr.response;

                if (
                    response.includes("<form") ||
                    response.includes('<div class="alert')
                ) {
                    return;
                }

                try {
                    const jsonResponse = JSON.parse(response);
                    if (
                        jsonResponse.success &&
                        jsonResponse.html &&
                        jsonResponse.settings &&
                        window.currentEditedWidgetEl
                    ) {
                        const content =
                            window.currentEditedWidgetEl.querySelector(
                                ".grid-stack-item-content"
                            );
                        if (content) {
                            content.style.transition =
                                "opacity 0.15s ease-in-out";
                            content.style.opacity = "0";

                            setTimeout(() => {
                                content.innerHTML = jsonResponse.html;
                                window.currentEditedWidgetEl.dataset.widgetSettings =
                                    JSON.stringify(jsonResponse.settings);

                                try {
                                    this.autoResize(
                                        window.currentEditedWidgetEl
                                    );
                                } catch (resizeErr) {
                                    console.error(
                                        "Error resizing widget:",
                                        resizeErr
                                    );
                                }
                                this.hasUnsavedChanges = true;
                                this.updateSaveButtonState();
                                this.history.push();
                                this.saveToLocalStorage();

                                this.dispatchWidgetEvent(
                                    "widgetSettingsSaved",
                                    {
                                        widgetName:
                                            window.currentEditedWidgetEl.getAttribute(
                                                "data-widget-name"
                                            ),
                                        widgetElement:
                                            window.currentEditedWidgetEl,
                                        settings: jsonResponse.settings,
                                    }
                                );

                                content.style.opacity = "1";

                                if (this.rightSidebarDialog) {
                                    this.rightSidebarDialog.hide();
                                }
                            }, 150);
                        }
                    }
                    return;
                } catch (parseErr) {
                    return;
                }
            }

            const respText = evt.detail.xhr.response;
            try {
                const respJson = JSON.parse(respText);
                if (
                    respJson.html &&
                    respJson.settings &&
                    window.currentEditedWidgetEl
                ) {
                    const content = window.currentEditedWidgetEl.querySelector(
                        ".grid-stack-item-content"
                    );
                    if (content) {
                        content.innerHTML = respJson.html;

                        window.currentEditedWidgetEl.dataset.widgetSettings =
                            JSON.stringify(respJson.settings);

                        this.autoResize(window.currentEditedWidgetEl);
                        this.hasUnsavedChanges = true;
                        this.updateSaveButtonState();
                        this.history.push();
                        this.saveToLocalStorage();

                        this.dispatchWidgetEvent("widgetSettingsSaved", {
                            widgetName:
                                window.currentEditedWidgetEl.getAttribute(
                                    "data-widget-name"
                                ),
                            widgetElement: window.currentEditedWidgetEl,
                            settings: respJson.settings,
                        });
                    }
                }
            } catch (e) {
                // Not JSON, ignore
            }
        } catch (e) {
            console.error("Error handling HTMX swap:", e);
        }
    }

    handleHtmxBeforeRequest(evt) {
        if (
            evt.detail.target?.id === "main" &&
            document.body.classList.contains("page-edit-mode")
        ) {
            if (this.hasUnsavedChanges) {
                const confirmLeave = confirm(
                    this.config.translations.unsavedChanges
                );
                if (!confirmLeave) {
                    evt.preventDefault();
                    return;
                }
            }
            this.disable(true);
        }
    }

    resetLayout() {
        if (!confirm(this.config.translations.resetConfirm)) return;

        // Remove all widgets except Content widget
        const items = Array.from(this.grid.getGridItems());
        items.forEach((item) => {
            if (item.getAttribute("data-widget-name") !== "Content") {
                this.grid.removeWidget(item);
            }
        });

        this.handleGridChange();
    }

    destroyGrid(ignoreHtmx = false) {
        if (!this.grid) return;

        this.grid.destroy();
        this.grid = null;
        this.history.clear();
        this.hasUnsavedChanges = false;
        this.updateUndoRedoButtons();
        this.updateSaveButtonState();

        try {
            localStorage.removeItem(this.getLocalStorageKey());
        } catch (e) {
            console.warn("Failed to clear saved layout from localStorage", e);
        }

        if (!ignoreHtmx) {
            htmx.ajax("GET", window.location.href, "#main", {
                swap: "innerHTML transition:true",
                headers: {
                    "X-CSRF-Token":
                        document.querySelector('meta[name="csrf-token"]')
                            ?.content || "",
                },
            });
        }
    }

    refreshWidget(widgetEl) {
        const widgetName = widgetEl.getAttribute("data-widget-name");
        const content = widgetEl.querySelector(".grid-stack-item-content");
        if (!content) return;

        const currentSettings = widgetEl.dataset.widgetSettings;

        content.style.pointerEvents = "none";
        content.innerHTML = this.createSkeleton();

        const tempDiv = document.createElement("div");
        tempDiv.widgetReference = widgetEl;
        tempDiv.setAttribute("hx-post", u("api/pages/render-widget"));
        tempDiv.setAttribute("hx-trigger", "load");
        tempDiv.setAttribute("hx-swap", "none");
        tempDiv.setAttribute(
            "hx-headers",
            JSON.stringify({
                "Content-Type": "application/json",
                "X-CSRF-Token":
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content || "",
            })
        );
        tempDiv.setAttribute(
            "hx-vals",
            JSON.stringify({
                widget_name: widgetName,
                settings: JSON.parse(currentSettings || "{}"),
            })
        );

        const onAfterLoad = (evt) => {
            try {
                const responseText = evt.detail.xhr.responseText;
                const json = JSON.parse(responseText);
                const targetWidget = evt.target.widgetReference;
                if (!targetWidget || !document.body.contains(targetWidget)) {
                    tempDiv.remove();
                    return;
                }
                const targetContent = targetWidget.querySelector(
                    ".grid-stack-item-content"
                );
                if (!targetContent) {
                    tempDiv.remove();
                    return;
                }

                if (json.html) {
                    targetContent.innerHTML = json.html;
                } else {
                    targetContent.innerHTML = "";
                }
                if (json.settings) {
                    targetWidget.dataset.widgetSettings = JSON.stringify(
                        json.settings
                    );
                }
            } catch (e) {
                console.error("Failed to parse JSON in refreshWidget:", e);
            }
            const targetWidget = evt.target.widgetReference;
            if (targetWidget) {
                const targetContent = targetWidget.querySelector(
                    ".grid-stack-item-content"
                );
                if (targetContent) {
                    targetContent.style.pointerEvents = "auto";
                }
            }

            tempDiv.removeEventListener("htmx:afterOnLoad", onAfterLoad);
            tempDiv.removeEventListener("htmx:responseError", onResponseError);
            tempDiv.remove();

            if (targetWidget && document.body.contains(targetWidget)) {
                this.autoResize(targetWidget);
                this.handleGridChange();

                this.dispatchWidgetEvent("widgetRefreshed", {
                    widgetName: targetWidget.getAttribute("data-widget-name"),
                    widgetElement: targetWidget,
                });
            }
        };

        const onResponseError = () => {
            const targetWidget = tempDiv.widgetReference;
            if (targetWidget && document.body.contains(targetWidget)) {
                const targetContent = targetWidget.querySelector(
                    ".grid-stack-item-content"
                );
                if (targetContent) {
                    targetContent.innerHTML = `<div class="widget-error">${this.config.translations.errorLoading}</div>`;
                    targetContent.style.pointerEvents = "auto";
                }
            }
            tempDiv.removeEventListener("htmx:afterOnLoad", onAfterLoad);
            tempDiv.removeEventListener("htmx:responseError", onResponseError);
            tempDiv.remove();
        };

        tempDiv.addEventListener("htmx:afterOnLoad", onAfterLoad);
        tempDiv.addEventListener("htmx:responseError", onResponseError);

        tempDiv.style.display = "none";
        content.appendChild(tempDiv);
        htmx.process(tempDiv);
    }

    setupCategoryHandlers() {
        document
            .querySelectorAll(".widget-category-header")
            .forEach((header) => {
                const newHeader = header.cloneNode(true);
                header.parentNode.replaceChild(newHeader, header);
            });

        document
            .querySelectorAll(".widget-category-header")
            .forEach((header) => {
                header.addEventListener("click", (e) =>
                    this.handleCategoryClick(e)
                );
            });

        // Show system category but make Content widget available for selection
        const systemCategory = document.querySelector(
            '.widget-category[data-category="system"]'
        );
        if (systemCategory) {
            // Не скрываем системную категорию полностью - пользователь должен видеть виджет Content
            systemCategory.style.display = "";
        }

        const firstCategory = document.querySelector(
            '.widget-category:not([data-category="system"]):first-child .widget-category-header'
        );
        if (firstCategory) {
            setTimeout(() => {
                this.toggleCategory(firstCategory, true);

                const categoriesContainer = document.querySelector(
                    ".page-edit-widgets-categories"
                );
                if (categoriesContainer) {
                    categoriesContainer.scrollLeft = 0;
                }
            }, 300);
        }

        const categoriesContainer = document.querySelector(
            ".page-edit-widgets-categories"
        );
        const scrollLeftBtn = document.querySelector(".categories-scroll-left");
        const scrollRightBtn = document.querySelector(
            ".categories-scroll-right"
        );

        if (categoriesContainer && scrollLeftBtn && scrollRightBtn) {
            const checkScroll = () => {
                const canScrollLeft = categoriesContainer.scrollLeft > 0;
                const canScrollRight =
                    categoriesContainer.scrollLeft <
                    categoriesContainer.scrollWidth -
                        categoriesContainer.clientWidth;

                const widgetsPanel =
                    document.querySelector(".page-edit-widgets");
                if (widgetsPanel) {
                    widgetsPanel.classList.toggle(
                        "can-scroll-left",
                        canScrollLeft
                    );
                    widgetsPanel.classList.toggle(
                        "can-scroll-right",
                        canScrollRight
                    );
                }
            };

            scrollLeftBtn.addEventListener("click", () => {
                categoriesContainer.scrollBy({
                    left: -150,
                    behavior: "smooth",
                });
            });

            scrollRightBtn.addEventListener("click", () => {
                categoriesContainer.scrollBy({
                    left: 150,
                    behavior: "smooth",
                });
            });

            categoriesContainer.addEventListener("scroll", checkScroll);

            checkScroll();

            window.addEventListener("resize", checkScroll);
        }
    }

    handleCategoryClick(e) {
        const header = e.currentTarget;
        const wasActive = header.classList.contains("active");

        document
            .querySelectorAll(".widget-category-header.active")
            .forEach((h) => {
                if (h !== header) {
                    this.toggleCategory(h, false);
                }
            });

        if (!wasActive) {
            this.toggleCategory(header, true);
        }
    }

    toggleCategory(header, show) {
        if (!header || window.pageEditor.grid === null) return;

        const content = header.nextElementSibling;
        const widgetsList = content?.querySelector(".page-edit-widgets-list");
        if (!content || !widgetsList) return;

        document
            .querySelectorAll(".widget-category-content.active")
            .forEach((el) => {
                if (el !== content) {
                    el.classList.remove("active");
                }
            });

        if (show) {
            header.classList.add("active");
            content.classList.add("active");

            const containerWidth = Math.min(window.innerWidth - 40, 900);
            content.style.width = `${containerWidth}px`;

            const widgets = content.querySelectorAll(".page-edit-widgets-item");
            widgets.forEach((widget, index) => {
                widget.style.opacity = "0";
                widget.style.transform = "translateY(10px)";
                widget.classList.remove("widget-animate");

                setTimeout(() => {
                    widget.style.transition =
                        "all 0.3s cubic-bezier(0.16, 1, 0.3, 1)";
                    widget.style.opacity = "1";
                    widget.style.transform = "translateY(0)";

                    setTimeout(() => {
                        widget.classList.add("widget-animate");
                    }, 100);
                }, 30 + index * 20);
            });

            this.activeCategory = header.dataset.category;

            document.addEventListener("click", this.boundHandleOutsideClick);
        } else {
            header.classList.remove("active");
            content.classList.remove("active");

            if (this.activeCategory === header.dataset.category) {
                this.activeCategory = null;
            }

            document.removeEventListener("click", this.boundHandleOutsideClick);
        }
    }

    handleOutsideClick(event) {
        return;
    }

    async openWidgetSettings(widgetEl) {
        const widgetName = widgetEl.getAttribute("data-widget-name");
        if (!widgetName) return;

        window.currentEditedWidgetEl = widgetEl;

        const rightSidebar = document.getElementById("page-edit-dialog");
        const sidebarContent = document.querySelector(
            "#page-edit-dialog-content"
        );
        // save button is inside the fetched form, query after injecting HTML

        if (!rightSidebar || !sidebarContent) {
            console.error("Right sidebar or content container not found");
            return;
        }

        if (!this.rightSidebarDialog) {
            this.rightSidebarDialog = new A11yDialog(rightSidebar);
            this.rightSidebarDialog.on("hide", () => {
                window.currentEditedWidgetEl = null;
            });
        }

        sidebarContent.innerHTML = `<div class="widget-settings-loading skeleton page-edit-skeleton widget-setting-loading"></div>
        <div class="widget-settings-loading skeleton page-edit-skeleton widget-setting-loading"></div>
        <div class="widget-settings-loading skeleton page-edit-skeleton widget-setting-loading"></div>
        <div class="widget-settings-loading skeleton page-edit-skeleton widget-setting-loading"></div>
        <div class="widget-settings-loading skeleton page-edit-skeleton widget-setting-loading"></div>`;

        this.rightSidebarDialog.show();

        try {
            const response = await csrfFetch(u("api/pages/widgets/settings-form"), {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    widget_name: widgetName,
                    settings: widgetEl.dataset.widgetSettings || "{}",
                }),
            });

            if (!response.ok) throw new Error("Failed to load settings form");

            const html = await response.text();

            sidebarContent.style.transition = "opacity 0.15s ease-in-out";
            sidebarContent.style.opacity = "0";

            setTimeout(() => {
                sidebarContent.innerHTML = html;

                const saveBtn = document.getElementById(
                    "widget-settings-save-btn"
                );
                if (saveBtn) {
                    const settingsUrl = u("api/pages/widgets/save-settings");
                    saveBtn.setAttribute("hx-post", settingsUrl);

                    const csrfToken = document.querySelector(
                        'meta[name="csrf-token"]'
                    )?.content;
                    if (csrfToken) {
                        saveBtn.setAttribute(
                            "hx-headers",
                            JSON.stringify({ "X-CSRF-Token": csrfToken })
                        );
                    }
                    saveBtn.setAttribute(
                        "hx-vals",
                        JSON.stringify({ widget_name: widgetName })
                    );

                    htmx.process(saveBtn);
                }

                htmx.process(sidebarContent);

                if (window.FluteSelect) {
                    window.FluteSelect.init();
                }

                this.dispatchWidgetEvent("widgetSettingsLoaded", {
                    widgetName,
                    widgetElement: widgetEl,
                    settingsContainer: sidebarContent,
                });

                sidebarContent.style.opacity = "1";
            }, 150);
        } catch (err) {
            console.error("Failed to load widget settings:", err);

            sidebarContent.style.transition = "opacity 0.15s ease-in-out";
            sidebarContent.style.opacity = "0";

            setTimeout(() => {
                sidebarContent.innerHTML = `<div class="alert alert-danger">${this.config.translations.errorLoading}</div>`;
                sidebarContent.style.opacity = "1";
            }, 150);
        }
    }

    loadFromLocalStorage() {
        try {
            const savedData = localStorage.getItem(this.getLocalStorageKey());
            if (savedData) {
                const layoutData = JSON.parse(savedData);
                if (Array.isArray(layoutData) && layoutData.length > 0) {
                    this.loadLayoutJson(layoutData);
                    this.hasUnsavedChanges = true;
                    this.updateSaveButtonState();
                    return true;
                }
            }
        } catch (err) {
            this.logError("loadFromLocalStorage", err);
            localStorage.removeItem(this.getLocalStorageKey());
        }
        return false;
    }

    handlePageLoad() {
        if (this.history) {
            this.history.clear();
            this.updateUndoRedoButtons();
        }

        this.currentPath = this.getCurrentPath();
        this.localStorageKey = this.getLocalStorageKey();
    }

    addContentWidget() {
        if (!this.grid) return;
        if (this.getCurrentPath() === "/") return;
        const existingContentWidget = this.elements.widgetGrid.querySelector(
            '[data-widget-name="Content"]'
        );
        if (existingContentWidget) return;
        try {
            const div = document.createElement("div");
            div.classList.add("grid-stack-item");
            div.setAttribute("data-widget-name", "Content");
            div.setAttribute("data-widget-id", "content-widget");
            div.setAttribute("data-system-widget", "true");
            div.setAttribute("gs-w", "12");
            div.setAttribute("gs-h", "4");
            div.setAttribute("gs-no-move", "true");
            div.dataset.widgetSettings = "{}";
            const content = document.createElement("div");
            content.classList.add("grid-stack-item-content");
            content.innerHTML = this.createSkeleton();
            div.appendChild(content);
            this.grid.makeWidget(div);
            this.initializeWidget(div, content);
        } catch (e) {
            this.logError("addContentWidget", e);
        }
    }

    autoFitSingleWidget(widgetElement, force = false) {
        if (!this.grid) return;

        if (!force && widgetElement.dataset.autoFitApplied === "true") return;

        const content = widgetElement.querySelector(".grid-stack-item-content");
        if (!content) return;

        const widgetName = widgetElement.getAttribute("data-widget-name") || "";

        let cellPx = parseFloat(this.grid.cellHeight() || 60);
        if (isNaN(cellPx) || cellPx <= 0) cellPx = 60;

        let desiredHeightPx;

        if (widgetName === "Content") {
            desiredHeightPx = 100;
            content.style.overflowY = "auto";
        } else {
            if (this.heightMode !== "auto") return;
            desiredHeightPx = content.scrollHeight;
        }

        const gridHeight = Math.max(2, Math.ceil(desiredHeightPx / cellPx));
        this.grid.update(widgetElement, { h: gridHeight });

        widgetElement.dataset.autoFitApplied = "true";
    }

    resizeWidgetToContentSafe(widgetEl) {
        try {
            this.autoFitSingleWidget(widgetEl, true);
        } catch (err) {
            this.logError("resizeWidgetToContentSafe", err);
        }
    }

    forceContentReflow(widgetEl) {
        if (!widgetEl) return;
        const content = widgetEl.querySelector(".grid-stack-item-content");
        if (content) {
            void content.offsetHeight;
        }
    }

    /**
     * Show a user-friendly error message.
     */
    showUserError(message) {
        notyf.error(message);
    }

    /**
     * Handle promise rejections with grace
     */
    handlePromiseRejection(reason) {
        this.logError("promise rejection", reason);
    }

    /**
     * Compact grid and fix heights – on user demand
     */
    autoPositionGrid() {
        if (!this.grid) return;
        try {
            this.grid.compact();
            if (this.heightMode === "auto") {
                this.autoFitWidgetHeights();
            }
            this.handleGridChange();
        } catch (err) {
            this.logError("autoPositionGrid", err);
            this.showUserError("Failed to auto-position widgets");
        }
    }

    /**
     * Apply container width mode
     */
    applyContainerWidth(isFullWidth) {
        const containers = document.querySelectorAll(".container");

        document.documentElement.setAttribute(
            "data-container-width",
            isFullWidth ? "fullwidth" : "container"
        );

        containers.forEach((container) => {
            if (!container.classList.contains("keep-container")) {
                container.classList.toggle("container-fullwidth", isFullWidth);
            }
        });
    }
}

/**
 * Initialize page editor functionality
 * Sets up the editor on page load and after HTMX content swaps
 */
function initializePageEditor() {
    const editorElements = document.querySelectorAll(
        "#page-change-button, .page-edit-navbar, .page-edit-widgets-sidebar"
    );

    editorElements.forEach((el) => {
        if (el) el.removeAttribute("style");
    });

    try {
        if (!window.pageEditor) {
            console.info("Initializing page editor");

            window.pageEditor = new PageEditor({
                gridOptions: {
                    margin: 10,
                    acceptWidgets: true,
                    sizeToContent: true,
                    disableDrag: false,
                    disableResize: false,
                    animate: true,
                    cellHeight: "auto",
                    column: 12,
                },
                widgetButtons: {
                    refresh: {
                        icon: "🔄",
                        tooltipKey: "def.refresh_widget",
                        onClick: (widgetEl, editor) => {
                            editor.refreshWidget(widgetEl);
                        },
                    },
                },
            });

            let resizeTimeout;
            window.addEventListener("resize", () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    if (window.pageEditor && window.pageEditor.grid) {
                        try {
                            window.pageEditor.grid.cellHeight(
                                window.pageEditor.grid.cellWidth() / 2
                            );
                        } catch (err) {
                            console.error(
                                "Error adjusting grid on resize:",
                                err
                            );
                        }
                    }
                }, 200);
            });
        } else {
            window.pageEditor.handlePageLoad();
        }

        window.toggleEditMode = (enable, ignoreHtmx = false) => {
            if (!window.pageEditor) {
                console.error("Page editor not initialized");
                return;
            }

            enable
                ? window.pageEditor.enable()
                : window.pageEditor.disable(ignoreHtmx);
        };
    } catch (err) {
        console.error("Failed to initialize page editor:", err);

        window.toggleEditMode = () => {
            alert("Page editor failed to initialize. Please refresh the page.");
        };
    }

    try {
        const currentPath = window.location.pathname || "/";
        const localStorageKey = `page-layout-${currentPath}`;
        const hasUnsavedChanges = localStorage.getItem(localStorageKey);

        if (hasUnsavedChanges) {
            console.info(
                "Unsaved changes found in local storage for path:",
                currentPath
            );

            setTimeout(() => {
                if (
                    window.pageEditor &&
                    typeof window.pageEditor.showErrorNotification ===
                        "function"
                ) {
                    window.pageEditor.showErrorNotification(
                        'You have unsaved changes. Click "Edit Page" to continue editing.'
                    );
                }
            }, 1000);
        }
    } catch (err) {
        console.error("Error checking for unsaved changes:", err);
    }
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initializePageEditor);
} else {
    initializePageEditor();
}

window.addEventListener("htmx:afterSwap", () => {
    setTimeout(initializePageEditor, 50);
});

document.addEventListener("htmx:responseError", (evt) => {
    console.error("HTMX response error:", evt.detail.error);

    if (
        window.pageEditor &&
        typeof window.pageEditor.showErrorNotification === "function"
    ) {
        window.pageEditor.showErrorNotification(
            "Error loading content. Please try again."
        );
    }
});

if (!AbortSignal.timeout) {
    AbortSignal.timeout = function timeout(ms) {
        const controller = new AbortController();
        setTimeout(
            () => controller.abort(new DOMException("TimeoutError")),
            ms
        );
        return controller.signal;
    };
}

window.getContainerWidthMode = function () {
    const toggle = document.getElementById("container-width-checkbox");
    return toggle && toggle.checked ? "fullwidth" : "container";
};
function getCsrfToken() {
    try {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.content) return meta.content;
        const m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
        if (m) {
            try { return decodeURIComponent(m[1]); } catch { return m[1]; }
        }
    } catch {}
    return "";
}

function csrfFetch(url, options = {}) {
    const headers = Object.assign({}, options.headers || {});
    const token = getCsrfToken();
    if (token && !('X-CSRF-Token' in headers)) headers['X-CSRF-Token'] = token;
    return fetch(url, { ...options, headers });
}
