animateChangeItems();
initHandlers();

window.addEventListener('htmx:afterSwap', function (event) {
    animateChangeItems();
    initHandlers();
});

function toggleSlide(element, show, button) {
    if (element.classList.contains('animating')) {
        return;
    }

    element.classList.add('animating');

    if (show) {
        element.style.height = '0px';
        element.classList.add('active');
        element.style.display = 'block';

        const height = element.scrollHeight;

        element.offsetHeight; // Trigger reflow

        element.style.height = height + 'px';
        button?.setAttribute('aria-expanded', 'true');
    } else {
        element.style.height = element.scrollHeight + 'px';
        element.offsetHeight; // Trigger reflow
        element.style.height = '0px';
        button?.setAttribute('aria-expanded', 'false');
    }

    element.addEventListener('transitionend', function handler(e) {
        if (e.propertyName !== 'height') return;

        if (!show) {
            element.classList.remove('active');
            element.style.display = '';
        }

        element.classList.remove('animating');
        element.removeEventListener('transitionend', handler);
    });
}

function animateChangeItems() {
    document
        .querySelectorAll('.changes-list .change-item')
        .forEach((item, index) => {
            item.style.animationDelay = `${index * 100}ms`;
        });
}

function animateHistoryItems(container) {
    container.querySelectorAll('.timeline-item').forEach((item, index) => {
        item.style.animationDelay = `${index * 100}ms`;
    });
}

function initHandlers() {
    document.querySelectorAll('.history-toggle').forEach((toggle) => {
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            const historyId = this.dataset.history;
            const historySection = document.getElementById(historyId);
            const icon = this.querySelector('.ph-caret-down-bold');

            if (historySection) {
                const isActive = historySection.classList.contains('active');

                document
                    .querySelectorAll('.update-history.active')
                    .forEach((section) => {
                        if (section !== historySection) {
                            const otherToggle = document.querySelector(
                                `[data-history="${section.id}"]`,
                            );
                            if (otherToggle) {
                                otherToggle.classList.remove('active');
                                otherToggle
                                    .querySelector('.ph-caret-down-bold')
                                    ?.classList.remove('active');
                                otherToggle.setAttribute(
                                    'aria-expanded',
                                    'false',
                                );
                            }
                            toggleSlide(section, false, otherToggle);
                        }
                    });

                this.classList.toggle('active');
                icon?.classList.toggle('active');
                toggleSlide(historySection, !isActive, this);

                if (!isActive) {
                    animateHistoryItems(historySection);
                    setTimeout(() => {
                        historySection.scrollIntoView({
                            behavior: 'smooth',
                            block: 'nearest',
                        });
                    }, 100);
                }
            }
        });
    });
}
