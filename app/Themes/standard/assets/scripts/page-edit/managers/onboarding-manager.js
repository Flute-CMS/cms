/**
 * Onboarding Manager - handles first-time user onboarding
 */
class OnboardingManager {
    constructor(editor) {
        this.editor = editor;
        this.config = editor.config;
        this.onboardingShownKey = this.config.storageKeys.onboardingShown;
        this.container = document.getElementById('pageEditOnboarding');
        this.slidesContainer = document.getElementById('onboardingSlides');
        this.indicatorsContainer = document.getElementById('onboardingIndicators');
        this.nextBtn = document.getElementById('onboardingNextBtn');
        this.currentSlideIndex = 0;
        this.slides = [];
    }

    /**
     * Initialize onboarding
     */
    initialize() {
        if (!this.container || !this.slidesContainer) return;
        if (localStorage.getItem(this.onboardingShownKey)) return;

        this.slides = this.slidesContainer.querySelectorAll('.page-edit-onboarding-slide');

        if (this.slides.length === 0) {
            this.container.style.display = 'none';
            return;
        }

        this.setupIndicators();
        this.setupEventListeners();
        this.show();
    }

    /**
     * Setup slide indicators
     */
    setupIndicators() {
        if (!this.indicatorsContainer) return;

        this.indicatorsContainer.innerHTML = '';

        this.slides.forEach((_, index) => {
            const indicator = document.createElement('div');
            indicator.classList.add('indicator');
            if (index === 0) indicator.classList.add('active');
            indicator.dataset.slideIndex = index;
            indicator.addEventListener('click', () => this.goToSlide(index));
            this.indicatorsContainer.appendChild(indicator);
        });
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        this.nextBtn?.addEventListener('click', () => {
            if (this.currentSlideIndex < this.slides.length - 1) {
                this.currentSlideIndex++;
                this.update();
            } else {
                this.finish();
            }
        });

        // Close on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.container?.classList.contains('active')) {
                this.finish();
            }
        });
    }

    /**
     * Update UI to reflect current slide
     */
    update() {
        this.slides.forEach((slide, index) => {
            slide.classList.toggle('active', index === this.currentSlideIndex);
        });

        const indicators = this.indicatorsContainer?.querySelectorAll('.indicator');
        if (indicators) {
            indicators.forEach((indicator, index) => {
                indicator.classList.toggle('active', index === this.currentSlideIndex);
            });
        }

        if (this.nextBtn) {
            const isLastSlide = this.currentSlideIndex === this.slides.length - 1;
            const finishText = typeof translate === 'function'
                ? translate('page.onboarding.finish')
                : 'Get Started';
            const nextText = typeof translate === 'function'
                ? translate('page.onboarding.next')
                : 'Next';
            this.nextBtn.innerHTML = isLastSlide ? finishText : nextText;
        }
    }

    /**
     * Go to a specific slide
     * @param {number} index - Slide index
     */
    goToSlide(index) {
        if (index < 0 || index >= this.slides.length) return;
        this.currentSlideIndex = index;
        this.update();
    }

    /**
     * Show the onboarding modal
     */
    show() {
        if (!this.container) return;

        this.container.style.display = 'flex';
        setTimeout(() => {
            this.container.classList.add('active');
            this.update();
        }, 50);
    }

    /**
     * Hide the onboarding modal
     */
    hide() {
        if (!this.container) return;

        this.container.classList.remove('active');
        setTimeout(() => {
            this.container.style.display = 'none';
        }, 300);
    }

    /**
     * Finish onboarding and mark as complete
     */
    finish() {
        this.hide();
        localStorage.setItem(this.onboardingShownKey, 'true');
    }

    /**
     * Reset onboarding (for testing)
     */
    reset() {
        localStorage.removeItem(this.onboardingShownKey);
        this.currentSlideIndex = 0;
    }

    /**
     * Check if onboarding has been shown
     * @returns {boolean}
     */
    hasBeenShown() {
        return !!localStorage.getItem(this.onboardingShownKey);
    }
}

window.FlutePageEdit.register('OnboardingManager', OnboardingManager);
