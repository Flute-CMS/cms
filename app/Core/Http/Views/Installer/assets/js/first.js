let currentGreeting = -1;
const greetings = getGreetings();
const sliderMessages = getSliderMessages();
const buttonMessages = getButtonMessages();
const selectMessages = getLanguageSelectionMessage();
const continueMessages = getContinueMessage();
const elements = getElements();
const langButtons = document.querySelectorAll('.lang-button');

const continueButton = document.querySelector('.installer-btn > p');
const langText = document.querySelector('.first-title');

let selectedLang = null;

if (localStorage.getItem('welcomeSeen') !== 'true') {
    initializeWelcome(elements, changeGreeting);
} else {
    showMainContent(elements);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('.logo-container').classList.add('start-animation');

    document.addEventListener('click', () => {
        hideWelcomeOverlay(elements);
        localStorage.setItem('welcomeSeen', 'true');
    });
});

langButtons.forEach(button => {
    button.addEventListener('click', function () {
        if (continueButton.getAttribute('aria-busy') === 'true')
            return;

        selectedLang = this.getAttribute('data-lang');
        document.querySelectorAll('.lang-button').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
        continueButton.classList.add('animate__fadeOut');
        continueButton.parentElement.removeAttribute('disabled');
        langText.classList.add('animate__fadeOut');

        setTimeout(() => {
            continueButton.textContent = continueMessages[selectedLang];
            continueButton.classList.remove('animate__fadeOut');
            continueButton.classList.add('animate__fadeIn');

            langText.textContent = selectMessages[selectedLang];
            langText.classList.remove('animate__fadeOut');
            langText.classList.add('animate__fadeIn');
        }, 50);
    });
});

function getGreetings() {
    return [
        { text: 'Welcome!', lang: 'en' },
        { text: 'Привет!', lang: 'ru' },
        { text: 'Привіт!', lang: 'uk' }
    ];
}

function getSliderMessages() {
    return {
        "en": "English",
        "ru": "Русский",
        "uk": "Українська мова"
    };
}

function getButtonMessages() {
    return {
        'en': 'Click any button',
        'ru': 'Нажмите на любую кнопку',
        'uk': 'Натисніть будь-яку кнопку'
    };
}

function getLanguageSelectionMessage() {
    return {
        'en': 'Select language',
        'ru': 'Выберите язык',
        'uk': 'Виберіть мову',
        'de': 'Sprache auswählen',
        'es': 'Seleccionar idioma',
        'fr': 'Sélectionner la langue',
        'it': 'Seleziona lingua',
        'pt': 'Selecione o idioma',
        'zh': '选择语言',
        'ja': '言語を選択',
        'ko': '언어 선택',
        'ar': 'اختر اللغة',
        'tr': 'Dil seçin',
        'nl': 'Taal selecteren',
        'sv': 'Välj språk',
        'da': 'Vælg sprog',
        'fi': 'Valitse kieli',
        'no': 'Velg språk',
        'pl': 'Wybierz język',
        'hu': 'Nyelv kiválasztása',
        'cs': 'Vyberte jazyk',
        'ro': 'Selectați limba',
        'el': 'Επιλέξτε γλώσσα',
        'he': 'בחר שפה',
        'th': 'เลือกภาษา',
        'hi': 'भाषा चुनें',
        'bn': 'ভাষা নির্বাচন করুন',
        'ta': 'மொழி தேர்ந்தெடுக்கவும்',
        'vi': 'Chọn ngôn ngữ',
        'id': 'Pilih bahasa',
        'ms': 'Pilih bahasa'
    };
}

function getContinueMessage() {
    return {
        'en': 'Continue',
        'ru': 'Продолжить',
        'uk': 'Продовжити',
        'de': 'Fortsetzen',
        'es': 'Continuar',
        'fr': 'Continuer',
        'it': 'Continua',
        'pt': 'Continuar',
        'zh': '继续',
        'ja': '続行',
        'ko': '계속',
        'ar': 'استمر',
        'tr': 'Devam et',
        'nl': 'Doorgaan',
        'sv': 'Fortsätt',
        'da': 'Fortsæt',
        'fi': 'Jatka',
        'no': 'Fortsett',
        'pl': 'Kontynuuj',
        'hu': 'Folytatás',
        'cs': 'Pokračovat',
        'ro': 'Continuați',
        'el': 'Συνέχισε',
        'he': 'המשך',
        'th': 'ดำเนินการต่อ',
        'hi': 'जारी रखें',
        'bn': 'চলাচল করুন',
        'ta': 'தொடர',
        'vi': 'Tiếp tục',
        'id': 'Lanjutkan',
        'ms': 'Teruskan'
    };
}

function getElements() {
    return {
        welcomeOverlay: document.querySelector('.welcome-overlay'),
        welcomeText: document.querySelector('.text-content'),
        clickButton: document.querySelector('.click-button'),
        cursor: document.querySelector('.cursor'),
        logoContainer: document.querySelector('.logo-container'),
        mainContent: document.querySelector('.container-installer'),
        sliderName: document.querySelector('.slider-name'),
        buttonNext: document.querySelector('.installer-btn'),
        progress: document.querySelector('.progress_block'),
    };
}

function initializeWelcome(elements, changeGreeting) {
    setTimeout(() => {
        if (currentGreeting === -1) {
            currentGreeting = -1;
        }
        changeGreeting();
    }, 500);

    elements.logoContainer.style.display = 'none';
    elements.logoContainer.style.opacity = '0';
    elements.logoContainer.style.display = 'flex';
    elements.mainContent.style.opacity = '0';
    elements.progress.classList.remove("animation");
}

function showMainContent(elements) {
    elements.welcomeOverlay.style.display = 'none';
    elements.mainContent.style.opacity = '1';
    elements.progress.classList.add("animation");
}

function hideWelcomeOverlay(elements) {
    elements.welcomeOverlay.style.opacity = '0';
    elements.progress.classList.add("animation");
    setTimeout(() => {
        elements.welcomeOverlay.style.display = 'none';
        elements.mainContent.style.opacity = '1';
    }, 500);
}

function changeGreeting() {
    currentGreeting = (currentGreeting + 1) % greetings.length;
    fadeOutText(elements.welcomeText, () => {
        elements.welcomeText.textContent = greetings[currentGreeting].text;
        fadeInText(elements.welcomeText, () => {
            setTimeout(changeGreeting, 3000);
        });
        changeButtonMessage(elements.clickButton, greetings[currentGreeting].lang);
        setTimeout(() => {
            elements.logoContainer.style.opacity = '.3';
        }, 500);
    });
}

function fadeInText(element, callback) {
    element.classList.remove('animate__fadeOut');
    element.classList.add('animate__fadeIn');
    if (callback) {
        setTimeout(callback, 100);
    }
}

function fadeOutText(element, callback) {
    element.classList.remove('animate__fadeIn');
    element.classList.add('animate__fadeOut');
    if (callback) {
        setTimeout(callback, 100);
    }
}

function changeButtonMessage(clickButton, lang) {
    clickButton.classList.add('animate__fadeOut');
    setTimeout(() => {
        const messageText = buttonMessages[lang] + ' <i class="ph-light ph-arrow-right"></i>';
        clickButton.innerHTML = messageText;
        clickButton.classList.remove('animate__fadeOut');
        clickButton.classList.add('animate__fadeIn');
    }, 50);
}

function typeText(text, welcomeText, cursor, callback) {
    let index = 0;
    function type() {
        if (index < text.length) {
            welcomeText.innerHTML += text[index];
            index++;
            setTimeout(type, 100);
            cursor.classList.add('staid');
        } else if (callback) {
            setTimeout(callback, 100);
            cursor.classList.remove('staid');
        }
    }
    type();
}
function eraseText(welcomeText, cursor, callback) {
    function erase() {
        if (welcomeText.textContent.length > 0) {
            welcomeText.textContent = welcomeText.textContent.slice(0, -1);
            setTimeout(erase, 100);
            cursor.classList.add('staid');
        } else if (callback) {
            setTimeout(callback, 100);
            cursor.classList.remove('staid');
        }
    }
    erase();
}

document.querySelector('.installer-btn').addEventListener('click', (e) => {
    e.preventDefault()
    e.stopPropagation()

    const btn = elements.buttonNext;

    btn.setAttribute('disabled', true);
    btn.setAttribute('aria-busy', true);
    clearErrors();

    fetch(u("install/1"), {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            lang: selectedLang,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
        })
    })
        .then(async response => {
            if (!response.ok) {
                return response.json().then(err => { throw err; });
            }
            return response.json();
        })
        .then(data => {
            btn.removeAttribute('aria-busy');
            btn.classList.add('success');
            btn.innerHTML = `<i class="ph ph-check"></i>`;
            btn.setAttribute('disabled', true);
            timerToRedirect();
        })
        .catch(error => {
            // Обработка ошибки
            btn.removeAttribute('aria-busy');
            btn.removeAttribute('disabled');

            let errorMessage = "An error occurred";
            if (error && error.error) {
                errorMessage = error.error;
            }

            addError(errorMessage);
        });
});
