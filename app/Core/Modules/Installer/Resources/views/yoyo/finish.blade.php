<div class="launch-step">
    <div class="finish-screen" id="finishScreen">
        <canvas id="confettiCanvas"></canvas>

        <div class="finish-screen__content">
            <div class="finish-screen__logo">
                <img src="@asset('assets/img/flute_logo.svg')" alt="Flute" />
            </div>

            <div class="finish-screen__icon">
                <svg viewBox="0 0 256 256" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="128" cy="128" r="120" stroke="currentColor" stroke-width="8" stroke-linecap="round" class="finish-circle" />
                    <polyline points="88,136 112,160 168,104" fill="none" stroke="currentColor" stroke-width="10" stroke-linecap="round" stroke-linejoin="round" class="finish-check" />
                </svg>
            </div>

            <h1 class="finish-screen__heading">{{ __('install.finish.heading') }}</h1>
            <p class="finish-screen__text">{{ __('install.finish.text') }}</p>

            <div class="finish-screen__progress">
                <div class="finish-screen__progress-bar" id="finishProgressBar"></div>
            </div>
            <span class="finish-screen__redirect">{{ __('install.finish.redirecting') }}</span>
        </div>

        <div class="finish-screen__footer">
            <span>Flute CMS v{{ Flute\Core\App::VERSION }}</span>
        </div>
    </div>

    <script>
    (function () {
        var canvas = document.getElementById('confettiCanvas');
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        var particles = [];

        var colors = [
            '#a5ff75', '#60a5fa', '#a78bfa', '#fbbf24',
            '#34d399', '#f87171', '#38bdf8', '#e879f9',
            '#fb923c', '#c084fc'
        ];

        var shapes = ['rect', 'circle', 'strip'];

        function resize() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        resize();
        window.addEventListener('resize', resize);

        function rand(min, max) {
            return Math.random() * (max - min) + min;
        }

        function createParticle(xMin, xMax, yMin, yMax) {
            return {
                x: rand(xMin, xMax),
                y: rand(yMin, yMax),
                w: rand(4, 9),
                h: rand(6, 14),
                shape: shapes[Math.floor(Math.random() * shapes.length)],
                color: colors[Math.floor(Math.random() * colors.length)],
                rotation: rand(0, 360),
                rotationSpeed: rand(-8, 8),
                vx: rand(-3, 3),
                vy: rand(2, 7),
                opacity: 1,
                gravity: rand(0.02, 0.05),
                wobble: rand(0, Math.PI * 2),
                wobbleSpeed: rand(0.03, 0.08),
                wobbleAmp: rand(0.3, 1.2),
                decay: rand(0.002, 0.005)
            };
        }

        function burst(count, xMin, xMax, yMin, yMax) {
            for (var i = 0; i < count; i++) {
                particles.push(createParticle(xMin, xMax, yMin, yMax));
            }
        }

        // Initial big burst from top
        burst(180, 0, canvas.width, -canvas.height * 0.6, -10);

        function draw(p) {
            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate((p.rotation * Math.PI) / 180);
            ctx.globalAlpha = p.opacity;
            ctx.fillStyle = p.color;

            if (p.shape === 'circle') {
                ctx.beginPath();
                ctx.arc(0, 0, p.w / 2, 0, Math.PI * 2);
                ctx.fill();
            } else if (p.shape === 'strip') {
                ctx.fillRect(-p.w / 2, -1, p.w, 2.5);
            } else {
                ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
            }

            ctx.restore();
        }

        var animId;
        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            var alive = 0;
            for (var i = 0; i < particles.length; i++) {
                var p = particles[i];
                if (p.opacity <= 0 || p.y > canvas.height + 40) continue;
                alive++;

                p.vy += p.gravity;
                p.y += p.vy;
                p.x += p.vx + Math.sin(p.wobble) * p.wobbleAmp;
                p.rotation += p.rotationSpeed;
                p.wobble += p.wobbleSpeed;
                p.opacity -= p.decay;
                if (p.opacity < 0) p.opacity = 0;

                draw(p);
            }

            if (alive > 0) {
                animId = requestAnimationFrame(animate);
            }
        }

        animId = requestAnimationFrame(animate);

        // Side bursts
        setTimeout(function () {
            burst(60, 0, canvas.width * 0.3, -80, -10);
            burst(60, canvas.width * 0.7, canvas.width, -80, -10);
            if (!animId) animId = requestAnimationFrame(animate);
        }, 800);

        // Center burst
        setTimeout(function () {
            burst(90, canvas.width * 0.25, canvas.width * 0.75, -60, -10);
            if (!animId) animId = requestAnimationFrame(animate);
        }, 2000);

        // Progress bar + redirect
        var bar = document.getElementById('finishProgressBar');
        var duration = 5500;
        var start = performance.now();

        function tick(now) {
            var elapsed = now - start;
            var t = Math.min(elapsed / duration, 1);
            // Ease out cubic for smooth feel
            var eased = 1 - Math.pow(1 - t, 3);
            if (bar) bar.style.width = (eased * 100) + '%';

            if (t < 1) {
                requestAnimationFrame(tick);
            } else {
                window.location.href = '/';
            }
        }
        requestAnimationFrame(tick);
    })();
    </script>
</div>
