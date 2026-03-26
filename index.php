<?php
$currentPath = dirname(realpath(__FILE__));
$publicPath = $currentPath . DIRECTORY_SEPARATOR . 'public';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Configuration Required</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --bg: #0c0c0c;
            --surface: #161616;
            --border: #2a2a2a;
            --text: #e5e5e5;
            --muted: #737373;
            --red: #ef4444;
            --red-dim: #7f1d1d;
            --green: #22c55e;
            --green-dim: #14532d;
        }

        body {
            font-family: 'JetBrains Mono', monospace;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            line-height: 1.6;
        }

        .container {
            width: 100%;
            max-width: 720px;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--red-dim);
            color: var(--red);
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1.5rem;
        }

        .badge::before {
            content: '';
            width: 6px;
            height: 6px;
            background: var(--red);
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        h1 {
            font-size: clamp(1.25rem, 4vw, 1.75rem);
            font-weight: 600;
            margin-bottom: 0.75rem;
            letter-spacing: -0.02em;
        }

        .subtitle {
            color: var(--muted);
            font-size: 0.875rem;
        }

        /* Path Diagram */
        .diagram {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .path-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
        }

        .path-row:last-child {
            margin-bottom: 0;
        }

        .path-row.wrong {
            background: var(--red-dim);
            border: 1px solid var(--red);
        }

        .path-row.correct {
            background: var(--green-dim);
            border: 1px solid var(--green);
        }

        .path-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.25rem;
        }

        .wrong .path-icon {
            background: var(--red);
            color: #fff;
        }

        .correct .path-icon {
            background: var(--green);
            color: #fff;
        }

        .path-content {
            flex: 1;
            min-width: 0;
        }

        .path-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 0.25rem;
        }

        .wrong .path-label { color: var(--red); }
        .correct .path-label { color: var(--green); }

        .path-value {
            font-size: 0.8rem;
            word-break: break-all;
            opacity: 0.9;
        }

        /* Arrow */
        .arrow {
            display: flex;
            justify-content: center;
            padding: 0.5rem 0;
            color: var(--muted);
        }

        .arrow svg {
            animation: bounce 1s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(4px); }
        }

        /* Instructions */
        .instructions {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 1rem;
            overflow: hidden;
        }

        .instruction-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            font-weight: 600;
            font-size: 0.875rem;
        }

        .instruction-content {
            padding: 1.5rem;
        }

        .server-block {
            margin-bottom: 1.5rem;
        }

        .server-block:last-child {
            margin-bottom: 0;
        }

        .server-name {
            display: inline-block;
            background: var(--border);
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .code-block {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            padding: 1rem;
            font-size: 0.75rem;
            overflow-x: auto;
            position: relative;
        }

        .code-block code {
            color: var(--green);
        }

        .copy-btn {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: var(--border);
            border: none;
            color: var(--muted);
            padding: 0.35rem 0.6rem;
            border-radius: 0.25rem;
            font-size: 0.65rem;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s;
        }

        .copy-btn:hover {
            background: var(--muted);
            color: var(--bg);
        }

        /* Footer */
        .footer {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
            text-align: center;
            color: var(--muted);
            font-size: 0.7rem;
        }

        .footer-en {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px dashed var(--border);
            font-size: 0.65rem;
            opacity: 0.7;
        }

        @media (max-width: 480px) {
            body { padding: 1rem; }
            .diagram, .instruction-content { padding: 1rem; }
            .path-row { flex-direction: column; text-align: center; gap: 0.5rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="badge">Ошибка конфигурации</div>
            <h1>Неверная корневая директория</h1>
            <p class="subtitle">Веб-сервер указывает не на ту папку</p>
        </header>

        <div class="diagram">
            <div class="path-row wrong">
                <div class="path-icon">✗</div>
                <div class="path-content">
                    <div class="path-label">Сейчас указано на / Current path</div>
                    <div class="path-value"><?= htmlspecialchars($currentPath) ?></div>
                </div>
            </div>

            <div class="arrow">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12l7 7 7-7"/>
                </svg>
            </div>

            <div class="path-row correct">
                <div class="path-icon">✓</div>
                <div class="path-content">
                    <div class="path-label">Нужно указать на / Required path</div>
                    <div class="path-value"><?= htmlspecialchars($publicPath) ?></div>
                </div>
            </div>
        </div>

        <div class="instructions">
            <div class="instruction-header">Как исправить / How to fix</div>
            <div class="instruction-content">
                <div class="server-block">
                    <div class="server-name">Apache</div>
                    <div class="code-block">
                        <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                        <code>DocumentRoot "<?= htmlspecialchars($publicPath) ?>"</code>
                    </div>
                </div>

                <div class="server-block">
                    <div class="server-name">Nginx</div>
                    <div class="code-block">
                        <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                        <code>root <?= htmlspecialchars($publicPath) ?>;</code>
                    </div>
                </div>
            </div>
        </div>

        <footer class="footer">
            <div>Измените DocumentRoot / root на папку <strong>/public</strong> и перезапустите сервер</div>
            <div class="footer-en">Change your DocumentRoot / root to the <strong>/public</strong> folder and restart the server</div>
        </footer>
    </div>

    <script>
        function copyCode(btn) {
            const code = btn.parentElement.querySelector('code').textContent;
            navigator.clipboard.writeText(code);
            btn.textContent = 'Copied!';
            setTimeout(() => btn.textContent = 'Copy', 1500);
        }
    </script>
</body>
</html>
