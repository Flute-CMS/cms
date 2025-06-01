const root = document.documentElement;

function parseCurrentThemeColors() {
    const currentTheme = root.getAttribute('data-theme');
    const isLightTheme = currentTheme === 'light';

    const colors = {
        '--accent': getRootColor('--accent'),
        '--primary': getRootColor('--primary'),
        '--secondary': getRootColor('--secondary'),
        '--background': getRootColor('--background'),
        '--text': getRootColor('--text'),
        '--border1': getRootColor('--border1').replace('rem', '')
    };

    const steps = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];
    Object.entries(colors).forEach(([variable, baseColor]) => {
        if (variable.startsWith('--') && !variable.includes('border')) {
            const shades = generateShades(baseColor, isLightTheme);
            steps.forEach((step) => {
                colors[`${variable}-${step}`] = shades[step];
            });
        }
    });

    return colors;
}

function getRootColor(variable) {
    return getComputedStyle(root).getPropertyValue(variable).trim();
}

function generateShades(baseColor, isLightTheme) {
    const shades = {};
    const steps = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];

    const darkTargets = {
        50: 96,
        100: 90,
        200: 80,
        300: 70,
        400: 60,
        500: 50,
        600: 40,
        700: 30,
        800: 20,
        900: 12,
        950: 8
    };

    if (isLightTheme) {
        return oldGenerateShadesLight(baseColor, true);
    }

    const hsl = tinycolor(baseColor).toHsl();
    steps.forEach(step => {
        if (step === 500) {
            shades[step] = tinycolor(baseColor).toHexString();
            return;
        }

        const targetL = darkTargets[step] / 100;
        const newColor = tinycolor({
            h: hsl.h,
            s: hsl.s,
            l: targetL
        });

        if (step >= 800) newColor.desaturate(5);

        shades[step] = newColor.toHexString();
    });
    return shades;
}

function oldGenerateShadesLight(baseColor, isLightTheme) {
    const shades = {};
    const steps = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];

    steps.forEach((step) => {
        if (step === 500) {
            shades[step] = tinycolor(baseColor).toHexString();
        } else if (isLightTheme) {
            if (step < 500) {
                const percentage = ((500 - step) / 450) * 100;
                shades[step] = tinycolor
                    .mix(baseColor, '#000000', percentage)
                    .toHexString();
            } else {
                const percentage = ((step - 500) / 450) * 100;
                shades[step] = tinycolor
                    .mix(baseColor, '#FFFFFF', percentage)
                    .toHexString();
            }
        } else {
            if (step < 500) {
                let percentage;
                if (step <= 100) {
                    percentage = 92 - (step - 50) * 0.3;
                } else if (step <= 300) {
                    percentage = 80 - (step - 100) * 0.2;
                } else {
                    percentage = 60 - (step - 300) * 0.15;
                }
                shades[step] = tinycolor
                    .mix(baseColor, '#FFFFFF', percentage)
                    .toHexString();
            } else {
                let percentage;
                if (step >= 900) {
                    percentage = 85 + (step - 900) * 0.3;
                } else if (step >= 700) {
                    percentage = 60 + (step - 700) * 0.12;
                } else {
                    percentage = 40 + (step - 500) * 0.1;
                }
                shades[step] = tinycolor
                    .mix(baseColor, '#000000', percentage)
                    .toHexString();
            }
        }
    });

    return shades;
}

document.addEventListener('DOMContentLoaded', () => {
    const colorBlocks = document.querySelectorAll('.color-block');
    const undoButton = document.getElementById('undo-button');
    const redoButton = document.getElementById('redo-button');
    const editColorsButton = document.getElementById('page-change-colors');
    const editColorsPanel = document.getElementById('page-colors-panel');
    const cancelColorsButton = document.getElementById('page-colors-cancel');
    const resetColorsButton = document.getElementById('reset-colors-button');
    const borderInput = document.getElementById('border-input');
    const borderEditorPanel = document.getElementById('border-editor-panel');
    const borderEditorCancel = document.getElementById('border-editor-cancel');
    const borderEditorSave = document.getElementById('border-editor-save');
    const borderDisplay = document.querySelector('.border-display');
    const previewBox = document.querySelector('.range-preview .preview-box');

    let history = [];
    let historyIndex = -1;
    let tempBorderValue = null;

    var pageEditButton = document.getElementById('page-edit-button');

    function setRootColor(variable, value) {
        root.style.setProperty(variable, value);

        if (variable === '--border1') {
            const border05Value = parseFloat(value) / 2 + 'rem';
            root.style.setProperty('--border05', border05Value);
        }
    }

    function removeInlineColor(variable) {
        root.style.removeProperty(variable);

        if (variable === '--border1') {
            root.style.removeProperty('--border05');
            return;
        }

        if (variable.startsWith('--') && !variable.includes('border')) {
            const steps = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];
            steps.forEach((step) => {
                const shadeVariable = `${variable}-${step}`;
                root.style.removeProperty(shadeVariable);
            });
        }
    }

    function getContrastRating(foreColor, backColor) {
        const contrastValue = tinycolor.readability(foreColor, backColor);
        let rating = 'Fail';

        if (
            tinycolor.isReadable(foreColor, backColor, {
                level: 'AAA',
                size: 'small',
            })
        ) {
            rating = 'AAA';
        } else if (
            tinycolor.isReadable(foreColor, backColor, {
                level: 'AA',
                size: 'small',
            })
        ) {
            rating = 'AA';
        }

        return { contrastValue, rating };
    }

    function immediateUpdateUI(variable, color) {
        setTimeout(() => {
            const block = document.querySelector(
                `.color-block[data-variable="${variable}"]`,
            );
            if (block) {
                const display = block.querySelector('.color-display');
                const input = block.querySelector('.color-input');
                if (input) {
                    display.style.backgroundColor = color;
                    input.value = tinycolor(color).toHexString();
                    checkContrastAndUpdateUI(variable, color);
                }
            }
        }, 50);
    }

    const debouncedUpdateUI = debounce((variable, color) => {
        immediateUpdateUI(variable, color);
    }, 100);

    function updateBlockUI(variable, color, isImmediate = false) {
        setRootColor(variable, color);

        if (isImmediate) {
            immediateUpdateUI(variable, color);
        } else {
            debouncedUpdateUI(variable, color);
        }
    }

    function checkContrastAndUpdateUI(variable, newColor) {
        let compareWith = null;

        if (variable === '--background') {
            compareWith = getRootColor('--text');
        } else if (variable === '--text') {
            compareWith = getRootColor('--background');
        } else if (variable === '--primary' || variable === '--accent') {
            compareWith = getRootColor('--background');
        } else {
            compareWith = getRootColor('--text');
        }

        const { contrastValue, rating } = getContrastRating(
            newColor,
            compareWith,
        );
        const block = document.querySelector(
            `.color-block[data-variable="${variable}"]`,
        );
        if (block) {
            const ratingSpan = block.querySelector('.contrast-rating');
            if (ratingSpan) {
                ratingSpan.textContent = `${rating} - ${contrastValue.toFixed(2)}`;
                let color =
                    rating === 'AAA'
                        ? 'success'
                        : rating === 'AA'
                            ? 'warning'
                            : 'fail';
                ratingSpan.classList.remove('success', 'fail', 'warning');
                ratingSpan.classList.add(color);
            }
        }
    }

    function applyColors(colorsObject) {
        for (const [variable, color] of Object.entries(colorsObject)) {
            updateBlockUI(variable, color, true);
        }
    }

    function recordHistory() {
        history = history.slice(0, historyIndex + 1);
        history.push(parseCurrentThemeColors());
        historyIndex++;
        updateUndoRedoButtons();
    }

    function updateUndoRedoButtons() {
        undoButton.disabled = historyIndex <= 0;
        redoButton.disabled = historyIndex >= history.length - 1;

        undoButton.setAttribute('aria-disabled', historyIndex <= 0);
        redoButton.setAttribute(
            'aria-disabled',
            historyIndex >= history.length - 1,
        );
    }

    function applyHistory(state) {
        for (const [variable, color] of Object.entries(state)) {
            updateBlockUI(variable, color, true);
        }

        updateAllContrastRatings();
        updateBorderPreview();
    }

    function updateBorderPreview() {
        const borderInput = document.getElementById('border-input');
        if (!borderInput) return;
        
        const borderValue = borderInput.value;
        const borderDisplay = borderInput.nextElementSibling;

        if (borderDisplay) {
            borderDisplay.textContent = `${borderValue}rem`;
        }

        const previewBox = document.querySelector('.range-preview .preview-box');
        if (previewBox) {
            previewBox.style.borderRadius = `${borderValue}rem`;
        }
    }

    const debouncedUpdateBorderCSS = debounce((value) => {
        setRootColor('--border1', `${value}rem`);
    }, 100);

    function initializeBlocksFromCurrentTheme() {
        const currentColors = parseCurrentThemeColors();

        colorBlocks.forEach((block) => {
            const variable = block.dataset.variable;
            if (variable && currentColors[variable]) {
                const colorValue = currentColors[variable];
                const colorInput = block.querySelector('.color-input');
                const colorDisplay = block.querySelector('.color-display');

                if (colorInput && colorDisplay) {
                    colorInput.value = tinycolor(colorValue).toHexString();
                    colorDisplay.style.backgroundColor = colorValue;

                    if (!variable.includes('border')) {
                        checkContrastAndUpdateUI(variable, colorValue);
                    }
                }
            }
        });

        if (borderInput) {
            borderInput.value = currentColors['--border1'];
            borderInput.nextElementSibling.textContent = `${currentColors['--border1']}rem`;
            updateBorderPreview();
        }
    }

    function updateShades(variable, baseColor) {
        if (variable.includes('border')) {
            return;
        }

        const currentTheme = root.getAttribute('data-theme');
        const isLightTheme = currentTheme === 'light';
        const shades = generateShades(baseColor, isLightTheme);

        for (const [step, shade] of Object.entries(shades)) {
            const shadeVariable = `${variable}-${step}`;
            setRootColor(shadeVariable, shade);
        }
    }

    function updateTextColorsForTheme() {
        const colorBlocks = document.querySelectorAll('.color-block');
        colorBlocks.forEach((block) => {
            const variable = block.dataset.variable;
            const baseColor = getRootColor(variable);
            const currentTheme = root.getAttribute('data-theme');
            const isLightTheme = currentTheme === 'light';
            const shades = generateShades(baseColor, isLightTheme);
        });
    }

    function initializeAccessibility() {
        if (undoButton) {
            if (!undoButton.hasAttribute('aria-label')) {
                undoButton.setAttribute('aria-label', 'Undo color change');
            }
            undoButton.setAttribute('role', 'button');
        }

        if (redoButton) {
            if (!redoButton.hasAttribute('aria-label')) {
                redoButton.setAttribute('aria-label', 'Redo color change');
            }
            redoButton.setAttribute('role', 'button');
        }

        if (editColorsButton) {
            editColorsButton.setAttribute('role', 'button');
            editColorsButton.setAttribute('aria-expanded', 'false');
            editColorsButton.setAttribute('aria-controls', 'page-colors-panel');
        }

        if (cancelColorsButton) {
            cancelColorsButton.setAttribute('role', 'button');
            cancelColorsButton.setAttribute(
                'aria-label',
                'Cancel color changes',
            );
        }

        if (document.getElementById('save-colors-button')) {
            const saveButton = document.getElementById('save-colors-button');
            saveButton.setAttribute('role', 'button');
            saveButton.setAttribute('aria-label', 'Save color changes');
        }

        colorBlocks.forEach(setupColorBlockAccessibility);

        document.addEventListener('keydown', function (e) {
            if (!editColorsPanel.classList.contains('show') && !borderEditorPanel.classList.contains('show')) return;

            if (e.key === 'Escape') {
                e.preventDefault();
                if (borderEditorPanel.classList.contains('show')) {
                    borderEditorCancel.click();
                } else {
                    cancelColorsButton.click();
                }
                return;
            }

            if (!editColorsPanel.classList.contains('show')) return;

            if (e.ctrlKey && e.key === 'z' && !undoButton.disabled) {
                e.preventDefault();
                undoButton.click();
            }

            if (
                (e.ctrlKey && e.key === 'y') ||
                (e.ctrlKey && e.shiftKey && e.key === 'z')
            ) {
                if (!redoButton.disabled) {
                    e.preventDefault();
                    redoButton.click();
                }
            }

            if (
                ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(
                    e.key,
                )
            ) {
                const activeElement = document.activeElement;
                if (activeElement && activeElement.closest('.color-block')) {
                    const currentBlock = activeElement.closest('.color-block');
                    const allBlocks = Array.from(colorBlocks);
                    const currentIndex = allBlocks.indexOf(currentBlock);

                    let targetIndex;
                    if (e.key === 'ArrowDown' || e.key === 'ArrowRight') {
                        targetIndex = (currentIndex + 1) % allBlocks.length;
                    } else {
                        targetIndex =
                            (currentIndex - 1 + allBlocks.length) %
                            allBlocks.length;
                    }

                    const targetBlock = allBlocks[targetIndex];
                    const targetDisplay =
                        targetBlock.querySelector('.color-display');
                    if (targetDisplay) {
                        e.preventDefault();
                        targetDisplay.focus();
                    }
                }
            }
        });

        if (borderInput) {
            borderInput.setAttribute('aria-valuenow', borderInput.value);
        }

        if (resetColorsButton) {
            resetColorsButton.setAttribute('role', 'button');
            resetColorsButton.setAttribute('aria-label', 'Сбросить все настройки к значениям по умолчанию');
        }
    }

    function setupColorBlockAccessibility(block) {
        const display = block.querySelector('.color-display');
        const input = block.querySelector('.color-input');
        const variable = block.getAttribute('data-variable');
        const colorName = variable.replace('--', '');

        if (display) {
            display.setAttribute('tabindex', '0');
            display.setAttribute('role', 'button');
            
            if (variable === '--border1') {
                display.setAttribute('aria-label', `Открыть редактор радиуса границ`);
            } else {
                display.setAttribute('aria-label', `Выбрать цвет для ${colorName}`);
            }

            if (!display.classList.contains('focus-visible')) {
                display.classList.add('focus-visible');
            }

            display.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    if (variable === '--border1') {
                        display.click();
                    } else if (input) {
                        input.click();
                    }
                }
            });
        }

        if (input) {
            input.setAttribute('aria-label', `Color picker for ${colorName}`);

            input.addEventListener('change', (event) => {
                const newColor = event.target.value;
                const colorAnnounce = document.createElement('div');
                colorAnnounce.setAttribute('role', 'status');
                colorAnnounce.setAttribute('aria-live', 'polite');
                colorAnnounce.className = 'sr-only';
                colorAnnounce.textContent = `${colorName} color changed to ${newColor}`;
                document.body.appendChild(colorAnnounce);

                setTimeout(() => {
                    document.body.removeChild(colorAnnounce);
                }, 1000);
            });
        }

        const ratingSpan = block.querySelector('.contrast-rating');
        if (ratingSpan) {
            ratingSpan.setAttribute(
                'aria-label',
                `Contrast rating for ${colorName}`,
            );
        }
    }

    if (borderDisplay) {
        borderDisplay.addEventListener('click', () => {
            openBorderEditor();
        });
    }

    function openBorderEditor() {
        tempBorderValue = getRootColor('--border1').replace('rem', '');
        
        if (borderInput) {
            if (borderInput.nextElementSibling) {
                borderInput.nextElementSibling.textContent = `${tempBorderValue}rem`;
            }
        }
        
        const previewBox = document.querySelector('.range-preview .preview-box');
        if (previewBox) {
            previewBox.style.borderRadius = `${tempBorderValue}rem`;
        }
        
        borderEditorPanel.classList.add('show');
    }

    if (borderEditorCancel) {
        borderEditorCancel.addEventListener('click', () => {
            closeBorderEditor(true);
        });
    }

    if (borderEditorSave) {
        borderEditorSave.addEventListener('click', () => {
            closeBorderEditor(false);
            recordHistory();
        });
    }

    function closeBorderEditor(isCancel) {
        if (isCancel && tempBorderValue !== null) {
            setRootColor('--border1', `${tempBorderValue}rem`);
            
            const borderInput = document.getElementById('border-input');
            if (borderInput) {
                if (borderInput.nextElementSibling) {
                    borderInput.nextElementSibling.textContent = `${tempBorderValue}rem`;
                }
            }
            
            const previewBox = document.querySelector('.range-preview .preview-box');
            if (previewBox) {
                previewBox.style.borderRadius = `${tempBorderValue}rem`;
            }
        }
        
        borderEditorPanel.classList.remove('show');
        tempBorderValue = null;
    }

    editColorsButton.addEventListener('click', () => {
        app.dropdowns.closeAllDropdowns();

        editColorsPanel.classList.add('show');
        pageEditButton.classList.add('hide');

        editColorsButton.setAttribute('aria-expanded', 'true');

        initializeBlocksFromCurrentTheme();
        recordHistory();

        const firstColorBlock = colorBlocks[0];
        if (firstColorBlock) {
            const display = firstColorBlock.querySelector('.color-display');
            if (display) {
                display.focus();
            }
        }
    });

    function cancelColorChanges() {
        root.removeAttribute('style');

        editColorsPanel.classList.remove('show');
        pageEditButton.classList.remove('hide');

        editColorsButton.setAttribute('aria-expanded', 'false');

        editColorsButton.focus();

        initializeBlocksFromCurrentTheme();

        updateAllContrastRatings();

        updateBorderPreview();

        borderEditorPanel.classList.remove('show');

        recordHistory();
    }

    cancelColorsButton.addEventListener('click', cancelColorChanges);

    undoButton.addEventListener('click', () => {
        if (historyIndex > 0) {
            historyIndex--;
            applyHistory(history[historyIndex]);
            updateUndoRedoButtons();
        }
    });

    redoButton.addEventListener('click', () => {
        if (historyIndex < history.length - 1) {
            historyIndex++;
            applyHistory(history[historyIndex]);
            updateUndoRedoButtons();
        }
    });

    colorBlocks.forEach((block) => {
        const display = block.querySelector('.color-display');
        const input = block.querySelector('.color-input');
        const variable = block.getAttribute('data-variable');

        if (variable === '--border1') {
            display.addEventListener('click', () => openBorderEditor());
        } else if (input) {
            display.addEventListener('click', () => input.click());

            input.addEventListener('input', (event) => {
                if(editColorsPanel.classList.contains('show')) {
                    const newColor = event.target.value;
                    updateBlockUI(variable, newColor);
                    updateShades(variable, newColor);
                }
            });

            input.addEventListener('change', (event) => {
                if(editColorsPanel.classList.contains('show')) {
                    const newColor = event.target.value;
                    updateShades(variable, newColor);
                    updateBlockUI(variable, newColor);
                    recordHistory();
                }
            });
        }
    });

    initializeAccessibility();

    resetColorsButton.addEventListener('click', () => {
        resetToDefaults();
    });

    if (borderInput) {
        borderInput.addEventListener('input', (event) => {
            const value = event.target.value;
            event.target.nextElementSibling.textContent = `${value}rem`;
            
            const previewBox = document.querySelector('.range-preview .preview-box');
            if (previewBox) {
                previewBox.style.borderRadius = `${value}rem`;
            }
            
            debouncedUpdateBorderCSS(value);
        });

        borderInput.addEventListener('change', (event) => {
            const value = event.target.value;
            setRootColor('--border1', `${value}rem`);
            recordHistory();
        });
    }

    const observer = new MutationObserver((mutationsList) => {
        for (const mutation of mutationsList) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'data-theme') {
                removeInlineColor('--accent');
                removeInlineColor('--primary');
                removeInlineColor('--secondary');
                removeInlineColor('--background');
                removeInlineColor('--text');
                removeInlineColor('--border1');
                if (editColorsPanel && editColorsPanel.classList.contains('show')) {
                    const newThemeColors = parseCurrentThemeColors();
                    applyColors(newThemeColors);
                    updateTextColorsForTheme();
                    updateBorderPreview();
                    recordHistory();
                }
            }
        }
    });

    observer.observe(root, { attributes: true });

    document.addEventListener('htmx:afterRequest', function (event) {
        if (event.target.id === 'save-colors-button') {
            editColorsPanel.classList.remove('show');
            pageEditButton.classList.remove('hide');

            editColorsButton.setAttribute('aria-expanded', 'false');

            editColorsButton.focus();
        }
    });

    function makeColorsKeyboardAccessible() {
        const colorSchemes = document.querySelectorAll('.js-theme-item');

        colorSchemes.forEach((item, index) => {
            item.setAttribute('tabindex', '0');
            item.setAttribute('role', 'radio');
            item.setAttribute(
                'aria-checked',
                item.classList.contains('is-selected') ? 'true' : 'false',
            );

            const colorGroup = item.closest('.js-theme-group');
            if (colorGroup) {
                const groupId =
                    'color-scheme-group-' +
                    Math.random().toString(36).substr(2, 9);
                colorGroup.setAttribute('role', 'radiogroup');
                colorGroup.setAttribute('aria-label', 'Color schemes');
                item.setAttribute('aria-setsize', colorSchemes.length);
                item.setAttribute('aria-posinset', index + 1);
                item.setAttribute('name', groupId);
            }

            item.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    item.click();
                }
            });
        });
    }

    makeColorsKeyboardAccessible();

    document.addEventListener('themeChanged', function () {
        const colorSchemes = document.querySelectorAll('.js-theme-item');
        colorSchemes.forEach((item) => {
            item.setAttribute(
                'aria-checked',
                item.classList.contains('is-selected') ? 'true' : 'false',
            );
        });
    });

    function updateAllContrastRatings() {
        colorBlocks.forEach((block) => {
            const variable = block.dataset.variable;
            if (variable && !variable.includes('border')) {
                const colorValue = getRootColor(variable);
                checkContrastAndUpdateUI(variable, colorValue);
            }
        });
    }

    function resetToDefaults() {
        const currentTheme = root.getAttribute('data-theme');
        const defaults = defaultValues[currentTheme];

        root.removeAttribute('style');

        const borderInput = document.getElementById('border-input');

        if (borderInput) {
            borderInput.value = defaults['--border1'];
            borderInput.nextElementSibling.textContent = `${defaults['--border1']}rem`;
        }

        if (previewBox) {
            previewBox.style.borderRadius = `${defaults['--border1']}rem`;
        }

        colorBlocks.forEach((block) => {
            const variable = block.dataset.variable;
            if (variable && defaults[variable]) {
                const colorValue = defaults[variable];
                const colorInput = block.querySelector('.color-input');
                const colorDisplay = block.querySelector('.color-display');

                if (colorInput && colorDisplay) {
                    colorInput.value = tinycolor(colorValue).toHexString();
                    colorDisplay.style.backgroundColor = colorValue;
                }
            }
        });

        updateAllContrastRatings();

        recordHistory();
    }
});
