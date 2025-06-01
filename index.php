<?php
$currentPath = realpath(__FILE__);
$publicPath = realpath(dirname(__FILE__) . '/public');
$basePath = dirname($currentPath);
$suggestedPath = $basePath . '/public';
$isCorrectPath = (strpos($_SERVER['SCRIPT_FILENAME'], '/public/') !== false);
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройка сервера | Server Setup</title>
    <style>
        :root {
            --font-sans: -apple-system, BlinkMacSystemFont, 'SF Pro Text', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            --font-sans-display: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            --font-mono: ui-monospace, SFMono-Regular, SF Mono, Menlo, Consolas, monospace;

            --bg: #ffffff;
            --bg-alt: #f5f5f7;
            --bg-card: #ffffff;
            --text: #1d1d1f;
            --text-secondary: #6e6e73;
            --border: rgba(0, 0, 0, 0.08);
            --accent: #0077ED;
            --accent-hover: #0066CA;
            --red: #FF453A;
            --error-bg: rgba(255, 69, 58, 0.12);

            --space-xxs: 0.375rem;
            --space-xs: 0.625rem;
            --space-sm: 0.875rem;
            --space-md: 1.5rem;
            --space-lg: 2.5rem;
            --space-xl: 4rem;

            --radius: 1rem;
            --radius-sm: 0.5rem;
            --transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            --shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.04), 0 0.125rem 0.5rem rgba(0, 0, 0, 0.06);

            --font-size-sm: 0.875rem;
            --font-size-base: 1rem;
            --font-size-md: 1.125rem;
            --font-size-lg: 1.375rem;
            --font-size-xl: 2rem;
            --font-size-xxl: 2.75rem;

            --line-height: 1.5;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #000000;
                --bg-alt: #151515;
                --bg-card: #1d1d1f;
                --text: #f5f5f7;
                --text-secondary: #a1a1a6;
                --border: rgba(255, 255, 255, 0.08);
                --accent: #0A84FF;
                --accent-hover: #007AFF;
                --error-bg: rgba(255, 69, 58, 0.15);
                --shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.2), 0 0.125rem 0.5rem rgba(0, 0, 0, 0.1);
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            font-size: 16px;
        }

        body {
            font-family: var(--font-sans);
            background-color: var(--bg);
            color: var(--text);
            line-height: var(--line-height);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: var(--space-md);
            letter-spacing: -0.015em;
        }

        .container {
            width: min(100%, 40rem);
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: var(--space-lg);
        }

        .header {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: var(--space-sm);
            margin-bottom: var(--space-md);
        }

        .icon {
            width: 4.5rem;
            height: 4.5rem;
            border-radius: 50%;
            background-color: var(--error-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.25rem;
            margin-bottom: var(--space-sm);
            box-shadow: var(--shadow);
        }

        h1 {
            font-family: var(--font-sans-display);
            font-size: var(--font-size-xl);
            font-weight: 600;
            letter-spacing: -0.025em;
            color: var(--text);
            line-height: 1.2;
        }

        .subtitle {
            font-size: var(--font-size-md);
            color: var(--text-secondary);
            max-width: 28rem;
            margin-bottom: var(--space-xs);
            line-height: 1.4;
        }

        .card {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: var(--space-md);
            box-shadow: var(--shadow);
        }

        .tabs {
            display: flex;
            gap: var(--space-xs);
            margin-bottom: var(--space-md);
        }

        .tab {
            padding: var(--space-xs) var(--space-md);
            border-radius: var(--radius-sm);
            font-size: var(--font-size-sm);
            font-weight: 500;
            color: var(--text-secondary);
            cursor: pointer;
            transition: var(--transition);
            background: transparent;
            border: 1px solid var(--border);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        }

        .tab.active {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }

        .content {
            display: none;
        }

        .content.active {
            display: block;
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .path-info {
            background: var(--bg-alt);
            border-radius: var(--radius-sm);
            padding: var(--space-md);
            font-size: var(--font-size-base);
            margin-bottom: var(--space-lg);
            border: 1px solid var(--border);
        }

        .path-row {
            display: flex;
            flex-direction: column;
            margin-bottom: var(--space-sm);
        }

        .path-row:last-child {
            margin-bottom: 0;
        }

        .path-label {
            font-weight: 500;
            color: var(--text-secondary);
            font-size: var(--font-size-sm);
            margin-bottom: var(--space-xxs);
        }

        .path-value {
            font-family: var(--font-mono);
            font-size: var(--font-size-sm);
            word-break: break-all;
            padding: var(--space-sm);
            background: var(--bg);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            color: var(--text);
        }

        .error-notice {
            display: flex;
            padding: var(--space-md);
            background: var(--error-bg);
            border-radius: var(--radius-sm);
            margin-bottom: var(--space-md);
            align-items: flex-start;
        }

        .error-icon {
            margin-right: var(--space-sm);
            color: var(--red);
            flex-shrink: 0;
            margin-top: 0.125rem;
        }

        .error-text {
            font-size: var(--font-size-base);
        }

        .error-title {
            font-weight: 600;
            margin-bottom: var(--space-xxs);
            color: var(--text);
        }

        .error-desc {
            color: var(--text-secondary);
            line-height: 1.4;
        }

        .btn {
            background: var(--accent);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            padding: var(--space-sm) var(--space-md);
            font-size: var(--font-size-base);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: var(--space-xs);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .instructions {
            margin-top: var(--space-lg);
        }

        .instructions-title {
            font-size: var(--font-size-md);
            font-weight: 600;
            margin-bottom: var(--space-md);
            color: var(--text);
        }

        .server-option {
            background: var(--bg-alt);
            border-radius: var(--radius-sm);
            padding: var(--space-md);
            margin-bottom: var(--space-sm);
            transition: var(--transition);
            border: 1px solid var(--border);
        }

        .server-option:last-child {
            margin-bottom: 0;
        }

        .server-name {
            font-weight: 600;
            display: flex;
            align-items: center;
            cursor: pointer;
            color: var(--text);
        }

        .server-steps {
            display: none;
            padding: var(--space-md) 0 0 var(--space-md);
            animation: fadeIn 0.4s ease-out;
        }

        .server-steps.active {
            display: block;
        }

        .server-step {
            margin-bottom: var(--space-sm);
            font-size: var(--font-size-base);
            color: var(--text-secondary);
            line-height: 1.5;
        }

        .server-step:last-child {
            margin-bottom: 0;
        }

        .server-step code {
            font-family: var(--font-mono);
            background: var(--bg);
            padding: 0.125rem 0.375rem;
            border-radius: 0.25rem;
            font-size: 0.85em;
            border: 1px solid var(--border);
            color: var(--accent);
        }

        .footer {
            text-align: center;
            font-size: var(--font-size-sm);
            color: var(--text-secondary);
            padding-bottom: var(--space-md);
        }

        strong {
            font-weight: 600;
            color: var(--accent);
        }

        @media (max-width: 480px) {
            :root {
                --space-md: 1.25rem;
                --space-lg: 2rem;
            }

            .card {
                padding: var(--space-md);
            }

            h1 {
                font-size: 1.75rem;
            }

            .subtitle {
                font-size: 1rem;
            }

            .icon {
                width: 4rem;
                height: 4rem;
                font-size: 2rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <header class="header">
            <div class="icon">⚠️</div>
            <h1>Требуется настройка сервера</h1>
            <p class="subtitle">
                Веб-сервер настроен неправильно. Требуется указать папку <strong>/public</strong> в качестве корневой директории.
            </p>
        </header>

        <main class="card">
            <div class="tabs">
                <button class="tab active" onclick="switchTab('ru')">Русский</button>
                <button class="tab" onclick="switchTab('en')">English</button>
            </div>

            <div id="content-ru" class="content active">
                <div class="error-notice">
                    <div class="error-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 8V12M12 16H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <div class="error-text">
                        <div class="error-title">Некорректная конфигурация веб-сервера</div>
                        <div class="error-desc">Сервер использует корневую директорию проекта вместо папки /public</div>
                    </div>
                </div>

                <div class="path-info">
                    <div class="path-row">
                        <div class="path-label">Текущий путь:</div>
                        <div class="path-value"><?php echo htmlspecialchars($currentPath); ?></div>
                    </div>
                    <div class="path-row">
                        <div class="path-label">Требуемый путь:</div>
                        <div class="path-value"><?php echo htmlspecialchars($publicPath ?: $suggestedPath); ?></div>
                    </div>
                </div>

                <button class="btn" onclick="copyPath('<?php echo addslashes($publicPath ?: $suggestedPath); ?>')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8 5H6C4.89543 5 4 5.89543 4 7V19C4 20.1046 4.89543 21 6 21H16C17.1046 21 18 20.1046 18 19V7C18 5.89543 17.1046 5 16 5H14M8 5V3C8 1.89543 8.89543 1 10 1H14C15.1046 1 16 1.89543 16 3V5M8 5H14" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Скопировать правильный путь
                </button>

                <div class="instructions">
                    <div class="instructions-title">Инструкции по настройке</div>

                    <div class="server-option">
                        <div class="server-name" onclick="toggleServer('apache-ru')">
                            <svg style="margin-right: 8px" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 5L16 12L9 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Apache
                        </div>
                        <div id="apache-ru" class="server-steps">
                            <div class="server-step">1. Откройте файл конфигурации сайта (обычно в <code>/etc/apache2/sites-available/</code>)</div>
                            <div class="server-step">2. Найдите параметр <code>DocumentRoot</code></div>
                            <div class="server-step">3. Измените его на <code>DocumentRoot <?php echo htmlspecialchars($publicPath ?: "{путь к проекту}/public"); ?></code></div>
                            <div class="server-step">4. Перезапустите Apache: <code>sudo service apache2 restart</code></div>
                        </div>
                    </div>

                    <div class="server-option">
                        <div class="server-name" onclick="toggleServer('nginx-ru')">
                            <svg style="margin-right: 8px" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 5L16 12L9 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Nginx
                        </div>
                        <div id="nginx-ru" class="server-steps">
                            <div class="server-step">1. Откройте файл конфигурации сайта (обычно в <code>/etc/nginx/sites-available/</code>)</div>
                            <div class="server-step">2. Найдите параметр <code>root</code></div>
                            <div class="server-step">3. Измените его на <code>root <?php echo htmlspecialchars($publicPath ?: "{путь к проекту}/public"); ?>;</code></div>
                            <div class="server-step">4. Перезапустите Nginx: <code>sudo service nginx restart</code></div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="content-en" class="content">
                <div class="error-notice">
                    <div class="error-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 8V12M12 16H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <div class="error-text">
                        <div class="error-title">Incorrect web server configuration</div>
                        <div class="error-desc">The server is using the project root directory instead of /public folder</div>
                    </div>
                </div>

                <div class="path-info">
                    <div class="path-row">
                        <div class="path-label">Current path:</div>
                        <div class="path-value"><?php echo htmlspecialchars($currentPath); ?></div>
                    </div>
                    <div class="path-row">
                        <div class="path-label">Required path:</div>
                        <div class="path-value"><?php echo htmlspecialchars($publicPath ?: $suggestedPath); ?></div>
                    </div>
                </div>

                <button class="btn" onclick="copyPath('<?php echo addslashes($publicPath ?: $suggestedPath); ?>')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8 5H6C4.89543 5 4 5.89543 4 7V19C4 20.1046 4.89543 21 6 21H16C17.1046 21 18 20.1046 18 19V7C18 5.89543 17.1046 5 16 5H14M8 5V3C8 1.89543 8.89543 1 10 1H14C15.1046 1 16 1.89543 16 3V5M8 5H14" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Copy correct path
                </button>

                <div class="instructions">
                    <div class="instructions-title">Configuration instructions</div>

                    <div class="server-option">
                        <div class="server-name" onclick="toggleServer('apache-en')">
                            <svg style="margin-right: 8px" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 5L16 12L9 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Apache
                        </div>
                        <div id="apache-en" class="server-steps">
                            <div class="server-step">1. Open your site configuration file (usually in <code>/etc/apache2/sites-available/</code>)</div>
                            <div class="server-step">2. Find the <code>DocumentRoot</code> parameter</div>
                            <div class="server-step">3. Change it to <code>DocumentRoot <?php echo htmlspecialchars($publicPath ?: "{path to project}/public"); ?></code></div>
                            <div class="server-step">4. Restart Apache: <code>sudo service apache2 restart</code></div>
                        </div>
                    </div>

                    <div class="server-option">
                        <div class="server-name" onclick="toggleServer('nginx-en')">
                            <svg style="margin-right: 8px" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 5L16 12L9 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Nginx
                        </div>
                        <div id="nginx-en" class="server-steps">
                            <div class="server-step">1. Open your site configuration file (usually in <code>/etc/nginx/sites-available/</code>)</div>
                            <div class="server-step">2. Find the <code>root</code> parameter</div>
                            <div class="server-step">3. Change it to <code>root <?php echo htmlspecialchars($publicPath ?: "{path to project}/public"); ?>;</code></div>
                            <div class="server-step">4. Restart Nginx: <code>sudo service nginx restart</code></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer class="footer">
            Flute CMS made by Flames with ❤️
        </footer>
    </div>

    <script>
        function switchTab(lang) {
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.content').forEach(content => content.classList.remove('active'));

            const tabIndex = lang === 'ru' ? 0 : 1;
            document.querySelector(`.tab:nth-child(${tabIndex + 1})`).classList.add('active');
            document.getElementById(`content-${lang}`).classList.add('active');
        }

        function copyPath(path) {
            navigator.clipboard.writeText(path).then(() => {
                const isRuTab = document.getElementById('content-ru').classList.contains('active');
                const btn = document.querySelector('.content.active .btn');
                const originalText = btn.innerHTML;

                btn.innerHTML = isRuTab ?
                    '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 13L9 17L19 7" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Скопировано!' :
                    '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 13L9 17L19 7" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Copied!';

                setTimeout(() => {
                    btn.innerHTML = originalText;
                }, 2000);
            });
        }

        function toggleServer(id) {
            const steps = document.getElementById(id);
            const allSteps = document.querySelectorAll('.server-steps');

            allSteps.forEach(stepEl => {
                if (stepEl.id !== id) {
                    stepEl.classList.remove('active');
                }
            });

            steps.classList.toggle('active');
        }
    </script>
</body>

</html>