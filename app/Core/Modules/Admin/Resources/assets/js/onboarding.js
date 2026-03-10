(function () {
    var dataEl = document.getElementById('onboarding-data');
    if (!dataEl) return;

    var config = JSON.parse(dataEl.textContent);
    var steps = config.steps;
    var btnNext = config.next;
    var btnPrev = config.prev;
    var btnDone = config.finish;

    function markDone() {
        document.cookie = 'admin_onboarding_done=1; path=/; max-age=' + (365 * 86400) + '; SameSite=Lax';
    }

    function startTour() {
        if (typeof Shepherd === 'undefined') return;

        var visible = steps.filter(function (s) {
            return !s.attachTo || document.querySelector(s.attachTo);
        });
        if (!visible.length) return;

        var total = visible.length;

        var tour = new Shepherd.Tour({
            useModalOverlay: true,
            defaultStepOptions: {
                scrollTo: { behavior: 'smooth', block: 'center' },
                cancelIcon: { enabled: true },
                modalOverlayOpeningPadding: 6,
                modalOverlayOpeningRadius: 10,
            }
        });

        function bar(idx) {
            var pct = Math.round(((idx + 1) / total) * 100);
            return '<div class="onboarding-bar"><div class="onboarding-bar__fill" style="width:' + pct + '%"></div></div>';
        }

        visible.forEach(function (step, idx) {
            var buttons = [];

            // Meta counter — pushed left via margin-right:auto
            buttons.push({
                text: (idx + 1) + ' из ' + total,
                classes: 'onboarding-meta',
                disabled: true
            });

            if (idx > 0) {
                buttons.push({
                    text: btnPrev,
                    action: tour.back,
                    classes: 'shepherd-button-secondary'
                });
            }

            if (idx < total - 1) {
                buttons.push({ text: btnNext, action: tour.next });
            } else {
                buttons.push({
                    text: btnDone,
                    action: function () {
                        markDone();
                        tour.complete();
                    }
                });
            }

            tour.addStep({
                id: step.id,
                title: step.title,
                text: bar(idx) + '<p>' + step.text + '</p>',
                attachTo: step.attachTo
                    ? { element: step.attachTo, on: step.position || 'bottom' }
                    : undefined,
                buttons: buttons,
            });
        });

        tour.on('cancel', function () {
            markDone();
        });

        tour.start();
    }

    if (document.readyState === 'complete') {
        setTimeout(startTour, 500);
    } else {
        window.addEventListener('load', function () {
            setTimeout(startTour, 500);
        });
    }
})();
