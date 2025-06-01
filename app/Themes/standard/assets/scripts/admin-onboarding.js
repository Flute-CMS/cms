class AdminOnboarding {
    constructor() {
        this.container = document.getElementById('admin-onboarding');
        if (!this.container) return;

        this.stepsContainer = document.getElementById('onboarding-steps');
        this.slidesContainer = document.getElementById('onboarding-slides');
        this.slideTitle = document.getElementById('slide-title');
        this.progressBar = document.getElementById('onboarding-progress');
        this.prevButton = document.getElementById('onboarding-prev');
        this.nextButton = document.getElementById('onboarding-next');
        this.completeButton = document.getElementById('onboarding-complete');
        this.currentStepEl = document.getElementById('current-step');
        this.totalStepsEl = document.getElementById('total-steps');

        this.currentSlide = 0;
        this.totalSlides = this.slidesContainer.querySelectorAll('.admin-onboarding__slide').length;
        
        this.stepElements = Array.from(this.stepsContainer.querySelectorAll('.admin-onboarding__step'));
        this.slides = Array.from(this.slidesContainer.querySelectorAll('.admin-onboarding__slide'));
        
        if (this.container.classList.contains('hidden')) return;
        
        this.initEventListeners();
        
        this.updateUI();
        
        this.lockPageScroll();
        
        setTimeout(() => {
            this.container.classList.add('active');
        }, 300);
    }

    initEventListeners() {
        this.prevButton.addEventListener('click', () => this.prevSlide());
        this.nextButton.addEventListener('click', () => this.nextSlide());
        
        if (this.completeButton) {
            this.completeButton.addEventListener('click', () => this.completeOnboarding());
        }

        this.stepElements.forEach((step, index) => {
            step.addEventListener('click', () => this.goToSlide(index));
        });

        document.addEventListener('keydown', (e) => {
            if (!this.container.classList.contains('active')) return;
            
            if (e.key === 'ArrowLeft') {
                this.prevSlide();
            } else if (e.key === 'ArrowRight') {
                this.nextSlide();
            } else if (e.key === 'Escape') {
                this.completeOnboarding();
            }
        });
    }

    updateUI() {
        const activeStepTitle = this.stepElements[this.currentSlide].querySelector('.admin-onboarding__step-title').textContent;
        if (this.slideTitle) {
            this.slideTitle.textContent = activeStepTitle;
        }
        
        const progress = ((this.currentSlide + 1) / this.totalSlides) * 100;
        this.progressBar.style.width = `${progress}%`;
        
        if (this.currentStepEl) {
            this.currentStepEl.textContent = this.currentSlide + 1;
        }
        
        this.prevButton.disabled = this.currentSlide === 0;
        
        this.stepElements.forEach((step, index) => {
            if (index === this.currentSlide) {
                step.classList.add('active');
            } else {
                step.classList.remove('active');
            }
        });
        
        this.slides.forEach((slide, index) => {
            if (index === this.currentSlide) {
                slide.classList.add('active');
            } else {
                slide.classList.remove('active');
            }
        });
    }

    goToSlide(index) {
        if (index < 0 || index >= this.totalSlides) return;
        
        this.currentSlide = index;
        this.updateUI();
    }

    prevSlide() {
        if (this.currentSlide > 0) {
            this.currentSlide--;
            this.updateUI();
        }
    }

    nextSlide() {
        if (this.currentSlide < this.totalSlides - 1) {
            this.currentSlide++;
            this.updateUI();
        } else {
            this.completeOnboarding();
        }
    }
    
    lockPageScroll() {
        document.body.classList.add('no-scroll');
    }
    
    unlockPageScroll() {
        document.body.classList.remove('no-scroll');
    }

    completeOnboarding() {
        this.container.classList.remove('active');
        
        this.unlockPageScroll();
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        setCookie('admin_onboarding_completed', 'true');
        
        if (csrfToken) {
            fetch(u('api/tip/complete'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ tip: 'admin_onboarding' })
            }).then(response => {
                if (!response.ok) {
                    console.error('Failed to mark onboarding as completed');
                }
                notyf.success(translate('onboarding.completed'));
            }).catch(error => {
                console.error('Error marking onboarding as completed:', error);
            });
        }
        
        setTimeout(() => {
            this.container.classList.add('hidden');
        }, 400);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new AdminOnboarding();
}); 