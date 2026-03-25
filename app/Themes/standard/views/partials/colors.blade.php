@php
    $colors = app('flute.view.manager')->getColors();
@endphp

@php
    $skipVars = ['--gradient-stops', '--emoji-settings'];
    $themeModes = ['dark', 'light'];
    $defaultAccentColors = [
        'dark' => '#A5FF75',
        'light' => '#34c759',
    ];
    $defaultGradientStops = [
        ['color' => '#A5FF75', 'position' => 0, 'opacity' => 100],
        ['color' => '#121214', 'position' => 100, 'opacity' => 100],
    ];
    $defaultEmojiSettings = [
        'preset' => 'stars',
        'custom' => '⭐ ✨ 💫 🌟',
        'angle' => 0,
        'size' => 24,
        'spacing' => 64,
        'useAccent' => true,
    ];

    $parseNumber = static function ($value, $default) {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return (float) $default;
        }

        if (preg_match('/-?\d+(\.\d+)?/', $value, $matches)) {
            return (float) $matches[0];
        }

        return (float) $default;
    };

    $normalizeFont = static function ($fontValue) {
        if (!$fontValue || !is_string($fontValue)) {
            return $fontValue;
        }

        if ($fontValue === 'inherit' || str_contains($fontValue, 'var(')) {
            return $fontValue;
        }

        if (str_contains($fontValue, ',') || str_contains($fontValue, 'system-ui')) {
            return $fontValue;
        }

        $fontValue = trim($fontValue, "\"'");
        return "'" . $fontValue . "', system-ui, -apple-system, BlinkMacSystemFont, sans-serif";
    };

    $extractFontName = static function ($fontValue) {
        if (!$fontValue || !is_string($fontValue)) {
            return null;
        }

        if (preg_match("/['\"]?([^'\"',]+)/", $fontValue, $matches)) {
            $fontName = trim($matches[1]);
            if ($fontName && !in_array($fontName, ['sans-serif', 'serif', 'system-ui'], true)) {
                return $fontName;
            }
        }

        return null;
    };

    $hexToRgba = static function ($hex, $alpha) {
        if (!$hex) {
            return 'rgba(0,0,0,' . $alpha . ')';
        }

        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . $alpha . ')';
    };

    $normalizeStops = static function ($raw) use ($defaultGradientStops) {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
        }

        if (!is_array($raw) || count($raw) < 2) {
            $raw = $defaultGradientStops;
        }

        $stops = [];
        foreach ($raw as $stop) {
            if (!is_array($stop)) {
                continue;
            }

            $item = [
                'color' => $stop['color'] ?? '#000000',
                'position' => isset($stop['position']) ? (int) $stop['position'] : 0,
                'opacity' => isset($stop['opacity']) ? (int) $stop['opacity'] : 100,
            ];

            if (isset($stop['posX'])) {
                $item['posX'] = (float) $stop['posX'];
            }
            if (isset($stop['posY'])) {
                $item['posY'] = (float) $stop['posY'];
            }

            $stops[] = $item;
        }

        if (count($stops) < 2) {
            $stops = $defaultGradientStops;
        }

        usort($stops, static fn ($a, $b) => $a['position'] <=> $b['position']);

        return $stops;
    };

    $buildGradient = static function ($type, $angle, $posX, $posY, $intensity, array $stops) use ($hexToRgba) {
        if ($type === 'none' || count($stops) < 2) {
            return 'none';
        }

        $intensity = max(0, min(1, (float) $intensity));
        $parts = [];

        if ($type === 'radial') {
            $hasStopCenters = false;
            foreach ($stops as $stop) {
                if (array_key_exists('posX', $stop) || array_key_exists('posY', $stop)) {
                    $hasStopCenters = true;
                    break;
                }
            }

            if ($hasStopCenters) {
                $layers = [];
                foreach ($stops as $stop) {
                    $opacity = ($stop['opacity'] ?? 100) / 100;
                    $color = $hexToRgba($stop['color'] ?? '#000000', $opacity * $intensity);
                    $x = isset($stop['posX']) ? (float) $stop['posX'] : $posX;
                    $y = isset($stop['posY']) ? (float) $stop['posY'] : $posY;
                    $x = max(0, min(100, $x));
                    $y = max(0, min(100, $y));
                    $radius = isset($stop['position']) ? (float) $stop['position'] : 0;
                    $radius = max(0, min(100, $radius));
                    $fade = min(100, $radius + 25);

                    $layers[] = 'radial-gradient(circle at ' . $x . '% ' . $y . '%, ' . $color . ' 0%, ' . $color . ' ' . $radius . '%, rgba(0,0,0,0) ' . $fade . '%)';
                }

                return implode(', ', $layers);
            }
        }

        foreach ($stops as $stop) {
            $opacity = ($stop['opacity'] ?? 100) / 100;
            $parts[] = $hexToRgba($stop['color'] ?? '#000000', $opacity * $intensity) . ' ' . ($stop['position'] ?? 0) . '%';
        }

        $stopsStr = implode(', ', $parts);

        if ($type === 'linear') {
            return 'linear-gradient(' . $angle . 'deg, ' . $stopsStr . ')';
        }

        if ($type === 'radial') {
            return 'radial-gradient(circle at ' . $posX . '% ' . $posY . '%, ' . $stopsStr . ')';
        }

        if ($type === 'conic') {
            return 'conic-gradient(from ' . $angle . 'deg at ' . $posX . '% ' . $posY . '%, ' . $stopsStr . ')';
        }

        return 'none';
    };

    $hexToHsl = static function ($hex) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (strlen($hex) !== 6) {
            return ['h' => 0, 's' => 0, 'l' => 0.5];
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max === $min) {
            $h = $s = 0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

            if ($max === $r) {
                $h = (($g - $b) / $d + ($g < $b ? 6 : 0)) / 6;
            } elseif ($max === $g) {
                $h = (($b - $r) / $d + 2) / 6;
            } else {
                $h = (($r - $g) / $d + 4) / 6;
            }
        }

        return ['h' => round($h * 360), 's' => $s, 'l' => $l];
    };

    $calculateColorFilter = static function ($accentColor) use ($hexToHsl) {
        if (!$accentColor) {
            return 'none';
        }

        $hsl = $hexToHsl($accentColor);
        $hue = (int) $hsl['h'];
        $saturation = (int) ($hsl['s'] * 100);
        $lightness = (int) ($hsl['l'] * 100);

        $hueRotate = $hue - 30;
        $brightness = 0.5 + ($lightness / 100);

        return 'grayscale(1) sepia(1) saturate(' . max(100, $saturation * 3) . '%) hue-rotate(' . $hueRotate . 'deg) brightness(' . number_format($brightness, 2) . ')';
    };

    $buildEmojiPattern = static function (array $settings, string $accentColor, float $opacity) use ($defaultEmojiSettings, $calculateColorFilter) {
        $preset = $settings['preset'] ?? $defaultEmojiSettings['preset'];
        $custom = $settings['custom'] ?? $defaultEmojiSettings['custom'];
        $angle = (int) ($settings['angle'] ?? $defaultEmojiSettings['angle']);
        $size = (int) ($settings['size'] ?? $defaultEmojiSettings['size']);
        $spacing = (int) ($settings['spacing'] ?? $defaultEmojiSettings['spacing']);
        $useAccent = $settings['useAccent'] ?? $defaultEmojiSettings['useAccent'];

        $presets = [
            'stars' => ['⭐', '✨', '💫', '🌟'],
            'hearts' => ['❤️', '💕', '💖', '💗'],
            'fire' => ['🔥', '💥', '✨', '⚡'],
            'gaming' => ['🎮', '🕹️', '👾', '🎯'],
            'nature' => ['🌿', '🍃', '🌸', '🌺'],
            'space' => ['🚀', '🌙', '⭐', '🪐'],
        ];

        $emojis = [];
        if ($preset === 'custom') {
            $emojis = preg_split('/\s+/', trim($custom));
        } else {
            $emojis = $presets[$preset] ?? $presets['stars'];
        }
        $emojis = array_values(array_filter($emojis, static fn ($emoji) => $emoji !== ''));

        if (empty($emojis)) {
            $emojis = $presets['stars'];
        }

        $spacing = max(16, $spacing);
        $size = max(12, $size);

        $emojiTexts = '';
        $halfSize = $spacing / 2;
        foreach ($emojis as $index => $emoji) {
            $row = (int) floor($index / 2);
            $col = $index % 2;
            $x = $col * $spacing + $halfSize;
            $y = $row * $spacing + $halfSize;
            $emojiTexts .= '<text x="' . $x . '" y="' . $y . '" font-size="' . $size . '" transform="rotate(' . $angle . ' ' . $x . ' ' . $y . ')" dominant-baseline="middle" text-anchor="middle">' . htmlspecialchars($emoji, ENT_QUOTES, 'UTF-8') . '</text>';
        }

        $rows = (int) ceil(count($emojis) / 2);
        $svgWidth = $spacing * 2;
        $svgHeight = $spacing * $rows;

        $opacity = max(0, min(1, $opacity));
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $svgWidth . '" height="' . $svgHeight . '" viewBox="0 0 ' . $svgWidth . ' ' . $svgHeight . '"><g opacity="' . $opacity . '">' . $emojiTexts . '</g></svg>';
        $encodedSvg = rawurlencode($svg);

        $accentFilter = 'none';
        if ($useAccent && $accentColor) {
            $accentFilter = $calculateColorFilter($accentColor);
        }

        return [
            'pattern' => 'url("data:image/svg+xml,' . $encodedSvg . '")',
            'tile_width' => $svgWidth . 'px',
            'tile_height' => $svgHeight . 'px',
            'angle' => $angle . 'deg',
            'accent_filter' => $accentFilter,
        ];
    };

    $themeCustomization = [];
    $normalizedColors = [];
    $fontsToLoad = [];

    foreach ($themeModes as $mode) {
        $themeColors = $colors[$mode] ?? [];

        $gradientStops = $normalizeStops($themeColors['--gradient-stops'] ?? null);
        $gradientType = $themeColors['--gradient-type'] ?? 'none';
        $gradientAngleRaw = $themeColors['--gradient-angle'] ?? '135deg';
        $gradientPosXRaw = $themeColors['--gradient-pos-x'] ?? '50%';
        $gradientPosYRaw = $themeColors['--gradient-pos-y'] ?? '50%';
        $gradientIntensityRaw = $themeColors['--gradient-intensity'] ?? '0.15';

        $gradientAngle = $parseNumber($gradientAngleRaw, 135);
        $gradientPosX = $parseNumber($gradientPosXRaw, 50);
        $gradientPosY = $parseNumber($gradientPosYRaw, 50);
        $gradientIntensity = (float) $gradientIntensityRaw;

        $bgEffectOpacity = (float) ($themeColors['--bg-effect-opacity'] ?? 0.1);

        $emojiSettingsRaw = $themeColors['--emoji-settings'] ?? null;
        if (is_string($emojiSettingsRaw)) {
            $decodedEmoji = json_decode($emojiSettingsRaw, true);
            $emojiSettings = json_last_error() === JSON_ERROR_NONE ? $decodedEmoji : $defaultEmojiSettings;
        } elseif (is_array($emojiSettingsRaw)) {
            $emojiSettings = $emojiSettingsRaw;
        } else {
            $emojiSettings = $defaultEmojiSettings;
        }
        $emojiSettings = array_merge($defaultEmojiSettings, $emojiSettings);

        $themeCustomization[$mode] = [
            'vars' => [],
            'attrs' => [
                'nav-style' => $themeColors['--nav-style'] ?? 'default',
                'sidebar-style' => $themeColors['--sidebar-style'] ?? 'default',
                'sidebar-mode' => $themeColors['--sidebar-mode'] ?? 'full',
                'sidebar-position' => $themeColors['--sidebar-position'] ?? 'top',
                'nav-fixed' => ($themeColors['--nav-fixed'] ?? 'true') === 'true' ? 'true' : 'false',
                'nav-blur' => ($themeColors['--nav-blur'] ?? 'true') === 'true' ? 'true' : 'false',
                'nav-socials' => ($themeColors['--nav-socials'] ?? 'true') === 'true' ? 'true' : 'false',
                'gradient-type' => $gradientType,
                'bg-effect' => $themeColors['--bg-effect'] ?? 'none',
                'footer-type' => $themeColors['--footer-type'] ?? 'default',
                'footer-socials' => ($themeColors['--footer-socials'] ?? 'true') === 'true' ? 'true' : 'false',
                'footer-logo' => ($themeColors['--footer-logo'] ?? 'true') === 'true' ? 'true' : 'false',
                'animations' => ($themeColors['--animations'] ?? 'true') === 'true' ? 'true' : 'false',
                'hover-scale' => ($themeColors['--hover-scale'] ?? 'true') === 'true' ? 'true' : 'false',
                'content-align' => $themeColors['--content-align'] ?? 'left',
                'container-width' => $themeColors['--container-width'] ?? 'container',
            ],
            'gradient_stops' => $gradientStops,
            'emoji_settings' => $emojiSettings,
        ];

        foreach ($themeColors as $key => $value) {
            if (in_array($key, $skipVars, true)) {
                continue;
            }

            $themeCustomization[$mode]['vars'][$key] = $value;
        }

        if (isset($themeCustomization[$mode]['vars']['--font'])) {
            $themeCustomization[$mode]['vars']['--font'] = $normalizeFont($themeCustomization[$mode]['vars']['--font']);
        }

        if (isset($themeCustomization[$mode]['vars']['--font-header'])) {
            $themeCustomization[$mode]['vars']['--font-header'] = $normalizeFont($themeCustomization[$mode]['vars']['--font-header']);
        }

        if (!isset($themeCustomization[$mode]['vars']['--gradient-angle'])) {
            $themeCustomization[$mode]['vars']['--gradient-angle'] = $gradientAngleRaw;
        }
        if (!isset($themeCustomization[$mode]['vars']['--gradient-pos-x'])) {
            $themeCustomization[$mode]['vars']['--gradient-pos-x'] = $gradientPosXRaw;
        }
        if (!isset($themeCustomization[$mode]['vars']['--gradient-pos-y'])) {
            $themeCustomization[$mode]['vars']['--gradient-pos-y'] = $gradientPosYRaw;
        }
        if (!isset($themeCustomization[$mode]['vars']['--gradient-intensity'])) {
            $themeCustomization[$mode]['vars']['--gradient-intensity'] = (string) $gradientIntensity;
        }
        if (!isset($themeCustomization[$mode]['vars']['--bg-effect-opacity'])) {
            $themeCustomization[$mode]['vars']['--bg-effect-opacity'] = (string) $bgEffectOpacity;
        }

        $gradientCss = $buildGradient($gradientType, $gradientAngle, $gradientPosX, $gradientPosY, $gradientIntensity, $gradientStops);
        $themeCustomization[$mode]['vars']['--page-gradient'] = $gradientCss;

        $accentColor = $themeColors['--accent'] ?? $defaultAccentColors[$mode] ?? '#A5FF75';
        $emojiPattern = $buildEmojiPattern($emojiSettings, $accentColor, $bgEffectOpacity);
        if ($emojiPattern) {
            $themeCustomization[$mode]['vars']['--emoji-pattern'] = $emojiPattern['pattern'];
            $themeCustomization[$mode]['vars']['--emoji-tile-width'] = $emojiPattern['tile_width'];
            $themeCustomization[$mode]['vars']['--emoji-tile-height'] = $emojiPattern['tile_height'];
            $themeCustomization[$mode]['vars']['--emoji-angle'] = $emojiPattern['angle'];
            $themeCustomization[$mode]['vars']['--emoji-accent-filter'] = $emojiPattern['accent_filter'];
        }

        $normalizedColors[$mode] = $themeCustomization[$mode]['vars'];

        $fontValue = $themeCustomization[$mode]['vars']['--font'] ?? null;
        $headingValue = $themeCustomization[$mode]['vars']['--font-header'] ?? null;

        $fontName = $extractFontName($fontValue);
        if ($fontName && $fontName !== 'Manrope' && !in_array($fontName, $fontsToLoad, true)) {
            $fontsToLoad[] = $fontName;
        }

        if ($headingValue && $headingValue !== 'inherit') {
            $headingName = $extractFontName($headingValue);
            if ($headingName && $headingName !== 'Manrope' && !in_array($headingName, $fontsToLoad, true)) {
                $fontsToLoad[] = $headingName;
            }
        }
    }
@endphp

@if (!empty($normalizedColors))
    <style>
        @foreach ($normalizedColors as $mode => $vars)
            :root[data-theme="{{ $mode }}"] {
                @foreach ($vars as $key => $value)
                    {{ $key }}: {{ $value }};
                @endforeach
            }
        @endforeach
    </style>
@endif

@if(!empty($fontsToLoad))
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @foreach($fontsToLoad as $font)
        <link rel="preload" href="https://fonts.googleapis.com/css2?family={{ urlencode($font) }}:wght@400;500;600;700;800&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
        <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family={{ urlencode($font) }}:wght@400;500;600;700;800&display=swap"></noscript>
    @endforeach
@endif

<script>
    (function() {
        var themeData = {!! json_encode($themeCustomization, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!};
        window.__themeCustomization = themeData;

        var root = document.documentElement;

        function applyTheme(theme) {
            var data = themeData[theme] || themeData.dark || themeData.light;
            if (!data) {
                return;
            }

            var vars = data.vars || {};
            var newKeys = {};

            Object.keys(vars).forEach(function(key) {
                newKeys[key] = true;
                if (vars[key] !== null && vars[key] !== '') {
                    root.style.setProperty(key, vars[key]);
                }
            });

            for (var i = root.style.length - 1; i >= 0; i--) {
                var prop = root.style[i];
                if (prop.indexOf('--') === 0 && !newKeys[prop]) {
                    root.style.removeProperty(prop);
                }
            }

            var attrs = data.attrs || {};
            Object.keys(attrs).forEach(function(attr) {
                root.setAttribute('data-' + attr, String(attrs[attr]));
            });

            if (data.gradient_stops) {
                root.setAttribute('data-gradient-stops', JSON.stringify(data.gradient_stops));
            }

            if (data.emoji_settings) {
                root.setAttribute('data-emoji-settings', JSON.stringify(data.emoji_settings));
            }
        }

        window.applyThemeCustomization = applyTheme;

        applyTheme(root.getAttribute('data-theme') || 'dark');

        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'data-theme') {
                    applyTheme(root.getAttribute('data-theme') || 'dark');
                }
            });
        });
        observer.observe(root, { attributes: true, attributeFilter: ['data-theme'] });

        window.addEventListener('switch-theme', function(event) {
            var theme = event.detail && event.detail.theme;
            if (theme) {
                applyTheme(theme);
            }
        });
    })();
</script>
