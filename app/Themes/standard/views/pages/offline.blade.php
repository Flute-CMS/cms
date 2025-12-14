<!DOCTYPE html>
<html lang="{{ strtolower(app()->getLang()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('error.offline_title') }}</title>
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
            color: var(--red);
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

        .offline-list {
            list-style: none;
            margin: var(--space-md) 0;
            padding: 0;
            background: var(--bg-alt);
            border-radius: var(--radius-sm);
            padding: var(--space-md);
            border: 1px solid var(--border);
        }

        .offline-list li {
            margin-bottom: var(--space-sm);
            position: relative;
            padding-left: var(--space-md);
            color: var(--text-secondary);
        }

        .offline-list li:last-child {
            margin-bottom: 0;
        }

        .offline-list li::before {
            content: '•';
            color: var(--accent);
            position: absolute;
            left: 0;
            font-weight: bold;
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
            width: 100%;
            justify-content: center;
        }

        .btn:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .footer {
            text-align: center;
            font-size: var(--font-size-sm);
            color: var(--text-secondary);
            padding-bottom: var(--space-md);
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
            <div class="icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <h1>{{ __('error.offline_title') }}</h1>
            <p class="subtitle">
                {{ __('error.offline_description') }}
            </p>
        </header>

        <main class="card">
            <ul class="offline-list">
                <li>{{ __('error.offline_reason_1') }}</li>
                <li>{{ __('error.offline_reason_2') }}</li>
                <li>{{ __('error.offline_reason_3') }}</li>
                <li>{{ __('error.offline_reason_4') }}</li>
            </ul>

            <button onclick="window.location.reload()" class="btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                {{ __('error.offline_button') }}
            </button>
        </main>

        <footer class="footer">
            Flute CMS made by Flames with ❤️
        </footer>
    </div>

    <script>
        window.addEventListener('online', function() {
            window.location.reload();
        });

        setInterval(function() {
            if (navigator.onLine) {
                window.location.reload();
            }
        }, 30000);
    </script>
</body>

</html>