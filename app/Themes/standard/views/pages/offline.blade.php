<!DOCTYPE html>
<html lang="{{ strtolower(app()->getLang()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('error.offline_title') }}</title>
    <style>
        :root {
            --font: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --bg: #fafafa;
            --text: #1a1a1a;
            --text-muted: #737373;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0a0a0a;
                --text: #fafafa;
                --text-muted: #a3a3a3;
            }
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: var(--font);
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
            -webkit-font-smoothing: antialiased;
        }

        .content {
            max-width: 24rem;
        }

        .icon {
            width: 3rem;
            height: 3rem;
            margin: 0 auto 1.5rem;
            color: var(--text-muted);
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            letter-spacing: -0.025em;
        }

        .description {
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 2rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--accent);
            color: #fff;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn:hover {
            background: var(--accent-hover);
        }

        .btn svg {
            width: 1rem;
            height: 1rem;
        }

        footer {
            position: fixed;
            bottom: 1.5rem;
            font-size: 0.75rem;
            color: var(--text-muted);
        }
    </style>
</head>

<body>
    <div class="content">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 1l22 22M16.72 11.06A10.94 10.94 0 0119 12.55M5 12.55a10.94 10.94 0 015.17-2.39M10.71 5.05A16 16 0 0122.58 9M1.42 9a15.91 15.91 0 014.7-2.88M8.53 16.11a6 6 0 016.95 0M12 20h.01"/>
        </svg>
        <h1>{{ __('error.offline_title') }}</h1>
        <p class="description">{{ __('error.offline_description') }}</p>
        <button onclick="window.location.reload()" class="btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
            </svg>
            {{ __('error.offline_button') }}
        </button>
    </div>

    <footer>
        @if(config('app.copyright'))
            {{ config('app.copyright') }}
        @else
            © {{ date('Y') }} {{ config('app.name') }}
        @endif
    </footer>

    <script>
        window.addEventListener('online', () => window.location.reload());
    </script>
</body>

</html>