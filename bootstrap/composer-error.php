<?php

declare(strict_types=1);

/**
 * Display a user-friendly error page when Composer dependencies are broken
 */
function displayComposerError(Throwable $e, string $basePath): never
{
    http_response_code(500);

    $errorMessage = $e->getMessage();
    $isCli = php_sapi_name() === 'cli';

    if ($isCli) {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║              FLUTE - COMPOSER ERROR                          ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n\n";
        echo "Error: {$errorMessage}\n\n";
        echo "Dependencies are missing or corrupted.\n\n";
        echo "Option 1 - Via SSH:\n";
        echo "  1. cd {$basePath}\n";
        echo "  2. composer install\n";
        echo "  3. If fails: rm -rf vendor && composer install\n\n";
        echo "Option 2 - Manual (no SSH):\n";
        echo "  1. Go to: https://github.com/Flute-CMS/cms/releases\n";
        echo "  2. Download flute-with-vendor.zip from latest release\n";
        echo "  3. Extract vendor folder to your site\n\n";
        exit(1);
    }

    $safeError = htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8');
    $safePath = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8');

    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flute - Dependencies Error</title>
    <style>
        :root {
            --bg-primary: #0c0c0f;
            --bg-card: #141419;
            --bg-code: #1c1c24;
            --border: #2a2a35;
            --text-primary: #f4f4f5;
            --text-secondary: #a1a1aa;
            --text-muted: #71717a;
            --accent: #3b82f6;
            --accent-soft: rgba(59, 130, 246, 0.15);
            --error: #ef4444;
            --error-soft: rgba(239, 68, 68, 0.12);
            --success: #22c55e;
            --success-soft: rgba(34, 197, 94, 0.15);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: var(--text-primary);
            line-height: 1.5;
        }
        
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            max-width: 580px;
            width: 100%;
            overflow: hidden;
        }
        
        .header {
            padding: 24px 24px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 14px;
        }
        
        .header-icon {
            width: 40px;
            height: 40px;
            background: var(--error-soft);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .header-icon svg {
            width: 20px;
            height: 20px;
            color: var(--error);
        }
        
        .header-text h1 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 2px;
        }
        
        .header-text p {
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .content {
            padding: 20px 24px 24px;
        }
        
        .error-block {
            background: var(--error-soft);
            border: 1px solid rgba(239, 68, 68, 0.25);
            border-radius: 8px;
            padding: 12px 14px;
            margin-bottom: 20px;
        }
        
        .error-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--error);
            margin-bottom: 6px;
        }
        
        .error-message {
            font-family: "SF Mono", Monaco, Consolas, monospace;
            font-size: 12px;
            color: var(--text-primary);
            word-break: break-word;
            opacity: 0.9;
        }
        
        .solutions {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .solution {
            border-radius: 8px;
            padding: 16px;
        }
        
        .solution-ssh {
            background: var(--accent-soft);
            border: 1px solid rgba(59, 130, 246, 0.25);
        }
        
        .solution-manual {
            background: var(--success-soft);
            border: 1px solid rgba(34, 197, 94, 0.25);
        }
        
        .solution-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }
        
        .solution-badge {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 3px 8px;
            border-radius: 4px;
        }
        
        .solution-ssh .solution-badge {
            background: rgba(59, 130, 246, 0.2);
            color: var(--accent);
        }
        
        .solution-manual .solution-badge {
            background: rgba(34, 197, 94, 0.2);
            color: var(--success);
        }
        
        .solution-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .steps {
            list-style: none;
        }
        
        .step {
            display: flex;
            align-items: baseline;
            gap: 10px;
            margin-bottom: 8px;
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .step:last-child { margin-bottom: 0; }
        
        .step-num {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 600;
            flex-shrink: 0;
        }
        
        .solution-ssh .step-num {
            background: rgba(59, 130, 246, 0.2);
            color: var(--accent);
        }
        
        .solution-manual .step-num {
            background: rgba(34, 197, 94, 0.2);
            color: var(--success);
        }
        
        code {
            background: var(--bg-code);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: "SF Mono", Monaco, Consolas, monospace;
            font-size: 12px;
            color: #fbbf24;
        }
        
        a {
            color: var(--accent);
            text-decoration: none;
        }
        
        a:hover {
            text-decoration: underline;
        }
        
        .footer {
            padding: 14px 24px;
            border-top: 1px solid var(--border);
            background: rgba(0, 0, 0, 0.2);
            text-align: center;
            font-size: 12px;
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <div class="header-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            <div class="header-text">
                <h1>Composer Dependencies Error</h1>
                <p>Some required packages are missing or corrupted</p>
            </div>
        </div>
        
        <div class="content">
            <div class="error-block">
                <div class="error-label">Error Details</div>
                <div class="error-message">{$safeError}</div>
            </div>
            
            <div class="solutions">
                <div class="solution solution-ssh">
                    <div class="solution-header">
                        <span class="solution-badge">Option 1</span>
                        <span class="solution-title">Via SSH / Terminal</span>
                    </div>
                    <ol class="steps">
                        <li class="step">
                            <span class="step-num">1</span>
                            <span>Navigate to project: <code>cd {$safePath}</code></span>
                        </li>
                        <li class="step">
                            <span class="step-num">2</span>
                            <span>Run: <code>composer install</code></span>
                        </li>
                        <li class="step">
                            <span class="step-num">3</span>
                            <span>If fails: <code>rm -rf vendor && composer install</code></span>
                        </li>
                    </ol>
                </div>
                
                <div class="solution solution-manual">
                    <div class="solution-header">
                        <span class="solution-badge">Option 2</span>
                        <span class="solution-title">Manual Upload (No SSH)</span>
                    </div>
                    <ol class="steps">
                        <li class="step">
                            <span class="step-num">1</span>
                            <span>Go to <a href="https://github.com/Flute-CMS/cms/releases" target="_blank">GitHub Releases</a></span>
                        </li>
                        <li class="step">
                            <span class="step-num">2</span>
                            <span>Download <code>flute-with-vendor.zip</code> from latest release</span>
                        </li>
                        <li class="step">
                            <span class="step-num">3</span>
                            <span>Extract and upload <code>vendor</code> folder to your site root</span>
                        </li>
                        <li class="step">
                            <span class="step-num">4</span>
                            <span>Refresh this page</span>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
        
        <div class="footer">
            Flute CMS &mdash; Check file permissions if the problem persists
        </div>
    </div>
</body>
</html>
HTML;

    exit(1);
}

