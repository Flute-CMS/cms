document.addEventListener('DOMContentLoaded', () => {
    const scrollUpButton = document.querySelector('.scrollup');
    const showOffset = 500;
    let isAnimating = false;

    const smoothScrollToTop = () => {
        if (isAnimating) return;
        isAnimating = true;

        const startPosition = window.pageYOffset || document.documentElement.scrollTop;
        if (startPosition === 0) {
            isAnimating = false;
            return;
        }

        const pixelsPerFrame = Math.max(40, Math.floor(startPosition / 15));
        let currentPosition = startPosition;

        const scrollStep = () => {
            currentPosition = Math.max(0, currentPosition - pixelsPerFrame);
            
            window.scrollTo(0, currentPosition);
            
            if (currentPosition > 0) {
                requestAnimationFrame(scrollStep);
            } else {
                isAnimating = false;
            }
        };

        requestAnimationFrame(scrollStep);
    };

    scrollUpButton?.addEventListener('click', (e) => {
        e.preventDefault();
        smoothScrollToTop();
    });

    let scrollTimeout;
    window.addEventListener('scroll', () => {
        if (scrollTimeout) {
            clearTimeout(scrollTimeout);
        }

        scrollTimeout = setTimeout(() => {
            const shouldShow = window.scrollY > showOffset;
            console.log(showOffset, window.scrollY, shouldShow);
            scrollUpButton?.classList.toggle('is-visible', shouldShow);
        }, 100);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Home' && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            smoothScrollToTop();
        }
    });
});
