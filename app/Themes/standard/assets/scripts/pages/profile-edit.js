function setGlowColor() {
    const img = document.getElementById('avatar-image');
    const container = document.getElementById('avatar-container');

    if (!img || !container) {
        // console.warn('Avatar image or container not found.');
        return;
    }

    function getLuminance(r, g, b) {
        return 0.299 * r + 0.587 * g + 0.114 * b;
    }

    function getDominantColor(image) {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        canvas.width = image.naturalWidth;
        canvas.height = image.naturalHeight;

        ctx.drawImage(image, 0, 0, canvas.width, canvas.height);

        try {
            const imageData = ctx.getImageData(
                0,
                0,
                canvas.width,
                canvas.height,
            );
            const data = imageData.data;

            const colorCount = {};
            let maxCount = 0;
            let dominantColor = [0, 0, 0];

            for (let i = 0; i < data.length; i += 4) {
                const r = data[i];
                const g = data[i + 1];
                const b = data[i + 2];
                const a = data[i + 3];

                if (a === 0) continue;

                const key = `${Math.round(r / 10) * 10},${
                    Math.round(g / 10) * 10
                },${Math.round(b / 10) * 10}`;

                const luminance = getLuminance(r, g, b);
                if (luminance < 40 || luminance > 215) continue;

                colorCount[key] = (colorCount[key] || 0) + 1;

                if (colorCount[key] > maxCount) {
                    maxCount = colorCount[key];
                    dominantColor = key.split(',').map(Number);
                }
            }

            return dominantColor;
        } catch (error) {
            console.error('Error accessing image data:', error);
            return [0, 0, 0];
        }
    }

    function interpolateColor(startColor, endColor, factor) {
        const result = startColor.slice();
        for (let i = 0; i < 3; i++) {
            result[i] = Math.round(
                result[i] + factor * (endColor[i] - startColor[i]),
            );
        }
        return result;
    }

    /**
     * https://gist.github.com/gre/1650294
     */
    function easeInOutQuad(t) {
        return t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
    }

    function animateGlowColorChange(startColor, endColor, duration) {
        const startTime = performance.now();

        function animate(currentTime) {
            const elapsed = currentTime - startTime;
            let factor = Math.min(elapsed / duration, 1);
            factor = easeInOutQuad(factor); // Применить easing

            const currentColor = interpolateColor(startColor, endColor, factor);
            const rgbColor = `rgb(${currentColor[0]}, ${currentColor[1]}, ${currentColor[2]})`;

            // Обновить CSS-переменную --glow-color
            container.style.setProperty('--glow-color', rgbColor);

            if (elapsed < duration) {
                requestAnimationFrame(animate);
            }
        }

        requestAnimationFrame(animate);
    }

    function getCurrentGlowColor() {
        const computedStyle = getComputedStyle(container);
        const glowColor = computedStyle.getPropertyValue('--glow-color').trim();

        const matches = glowColor.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
        if (matches) {
            return [
                parseInt(matches[1]),
                parseInt(matches[2]),
                parseInt(matches[3]),
            ];
        }
        return [0, 0, 0];
    }

    let currentGlowColor = getCurrentGlowColor();

    function executeSetGlowColor() {
        try {
            const dominantColor = getDominantColor(img);
            const newGlowColor = dominantColor;

            animateGlowColorChange(currentGlowColor, newGlowColor, 500);

            currentGlowColor = newGlowColor;
        } catch (error) {
            console.error('Error setting glow color:', error);
        }
    }

    if (img.complete) {
        executeSetGlowColor();
    } else {
        img.addEventListener('load', executeSetGlowColor);
    }
}

$(document).ready(() => setGlowColor());

document.addEventListener('user-change', function (e) {
    const { name, avatar, banner, balance, uri, email } = e.detail;

    const profileAvatar = document.querySelector('[data-profile-avatar]');

    if (profileAvatar) {
        profileAvatar.src = u(avatar);
    }

    const profileBanner = document.querySelector('[data-profile-banner]');

    if (profileBanner) {
        profileBanner.src = u(banner);
    }

    const profileName = document.querySelector('[data-profile-name]');

    if (profileName) {
        profileName.textContent = name;
    }

    const profileBalance = document.querySelector('[data-profile-balance]');

    if (profileBalance) {
        const formatted = new Intl.NumberFormat('ru-RU').format(balance);
        profileBalance.textContent = formatted;
    }
    
    const profileUri = document.querySelector('[data-profile-uri]');

    if (profileUri) {
        profileUri.href = uri;
    }

    const profileEmail = document.querySelector('[data-profile-email]');

    if (profileEmail) {
        profileEmail.textContent = email;
    }
});

htmx.onLoad(() => {
    setGlowColor();
});

window.addEventListener('load', () => {
    setGlowColor();
});

document.body.addEventListener('htmx:afterSwap', function (evt) {
    if (evt.detail.target.id === 'tab-content') {
        const urlParams = new URLSearchParams(
            new URL(evt.detail.xhr.responseURL).search,
        );
        const currentTab = urlParams.get('tab') || 'main';

        document
            .querySelectorAll('.profile-edit__sidebar-item')
            .forEach(function (link) {
                link.classList.remove('active');
            });

        const activeLink = document.querySelector(
            `.profile-edit__sidebar-item[data-tab-path="${currentTab}"]`,
        );
        if (activeLink) {
            activeLink.classList.add('active');
        }
    }
});

var listenerAdded = false;

function createSocialWind(pageURL, pageTitle, popupWinWidth, popupWinHeight) {
    let left = (screen.width - popupWinWidth) / 2;
    let top = (screen.height - popupWinHeight) / 4;

    let myWindow = window.open(
        pageURL,
        pageTitle,
        'toolbar=no, location=no, directories=no, status=no, menubar=no, resizable=yes, width=' +
            popupWinWidth +
            ', height=' +
            popupWinHeight +
            ', top=' +
            top +
            ', left=' +
            left,
    );

    return myWindow;
}

$(document).on('click', '[data-connect]', function (e) {
    e.preventDefault();
    let url = $(this).data('connect');
    let newWindow = createSocialWind(url, 'Social bind', 1000, 800);

    if (!listenerAdded) {
        window.addEventListener('message', function (event) {
            if (event.data === 'authorization_success') {
                // notyf.success(event.data);
                location.reload();
            } else if (event.data && event.data.startsWith('authorization_error:')) {
                notyf.error(event.data.split(':')[1]);
            }
        });

        listenerAdded = true;
    }
});
