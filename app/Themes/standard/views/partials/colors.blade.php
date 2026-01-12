@php
    $colors = app('flute.view.manager')->getColors();
@endphp

@if (!empty($colors))
    <style>
        @isset($colors['dark'])
            :root[data-theme="dark"] {
                @foreach ($colors['dark'] as $key => $value)
                    {{ $key }}: {{ $value }};
                @endforeach
            }
            
            @if(isset($colors['dark']['--background-type']))
                @php
                    $bgType = $colors['dark']['--background-type'] ?? 'solid';
                    $bgColor = $colors['dark']['--background'] ?? '#1c1c1e';
                    $grad1 = $colors['dark']['--bg-grad1'] ?? ($colors['dark']['--accent'] ?? '#A5FF75');
                    $grad2 = $colors['dark']['--bg-grad2'] ?? ($colors['dark']['--primary'] ?? '#f2f2f7');
                    $grad3 = $colors['dark']['--bg-grad3'] ?? $bgColor;
                @endphp

                html[data-theme="dark"] body {
                    @if($bgType === 'linear-gradient')
                        background: linear-gradient(145deg, {{ $bgColor }} 0%, {{ $bgColor }} 60%, {{ $grad1 }}14 100%);
                    @elseif($bgType === 'radial-gradient')
                        background: radial-gradient(ellipse 120% 100% at 80% 0%, {{ $grad1 }}18 0%, {{ $bgColor }} 50%);
                    @elseif($bgType === 'mesh-gradient')
                        background: 
                            radial-gradient(ellipse at 15% 15%, {{ $grad1 }}14 0%, transparent 50%),
                            radial-gradient(ellipse at 85% 80%, {{ $grad2 }}10 0%, transparent 50%),
                            {{ $bgColor }};
                    @elseif($bgType === 'subtle-gradient')
                        background: linear-gradient(160deg, {{ $bgColor }} 0%, {{ $grad1 }}0a 100%);
                    @elseif($bgType === 'aurora-gradient')
                        background: 
                            radial-gradient(ellipse 150% 80% at 10% 20%, {{ $grad1 }}14 0%, transparent 50%),
                            radial-gradient(ellipse 100% 60% at 90% 30%, {{ $grad2 }}10 0%, transparent 50%),
                            radial-gradient(ellipse 120% 80% at 50% 90%, {{ $grad3 }}0c 0%, transparent 60%),
                            {{ $bgColor }};
                    @elseif($bgType === 'sunset-gradient')
                        background: linear-gradient(180deg, {{ $grad1 }}18 0%, {{ $grad1 }}0c 35%, {{ $bgColor }} 100%);
                    @elseif($bgType === 'ocean-gradient')
                        background: 
                            radial-gradient(ellipse 100% 50% at 50% 0%, {{ $grad1 }}14 0%, transparent 60%),
                            radial-gradient(ellipse 100% 50% at 50% 100%, {{ $grad2 }}10 0%, transparent 60%),
                            {{ $bgColor }};
                    @elseif($bgType === 'spotlight-gradient')
                        background: radial-gradient(ellipse 80% 60% at 75% 25%, {{ $grad1 }}1e 0%, {{ $grad1 }}0a 40%, {{ $bgColor }} 70%);
                    @else
                        background-color: {{ $bgColor }};
                    @endif
                }
            @endif
        @endisset

        @isset($colors['light'])
            :root[data-theme="light"] {
                @foreach ($colors['light'] as $key => $value)
                    {{ $key }}: {{ $value }};
                @endforeach
            }
            
            @if(isset($colors['light']['--background-type']))
                @php
                    $bgType = $colors['light']['--background-type'] ?? 'solid';
                    $bgColor = $colors['light']['--background'] ?? '#ffffff';
                    $grad1 = $colors['light']['--bg-grad1'] ?? ($colors['light']['--accent'] ?? '#34c759');
                    $grad2 = $colors['light']['--bg-grad2'] ?? ($colors['light']['--primary'] ?? '#1d1d1f');
                    $grad3 = $colors['light']['--bg-grad3'] ?? $bgColor;
                @endphp
                
                html[data-theme="light"] body {
                    @if($bgType === 'linear-gradient')
                        background: linear-gradient(145deg, {{ $bgColor }} 0%, {{ $bgColor }} 60%, {{ $grad1 }}0c 100%);
                    @elseif($bgType === 'radial-gradient')
                        background: radial-gradient(ellipse 120% 100% at 80% 0%, {{ $grad1 }}10 0%, {{ $bgColor }} 50%);
                    @elseif($bgType === 'mesh-gradient')
                        background: 
                            radial-gradient(ellipse at 15% 15%, {{ $grad1 }}0c 0%, transparent 50%),
                            radial-gradient(ellipse at 85% 80%, {{ $grad2 }}0a 0%, transparent 50%),
                            {{ $bgColor }};
                    @elseif($bgType === 'subtle-gradient')
                        background: linear-gradient(160deg, {{ $bgColor }} 0%, {{ $grad1 }}06 100%);
                    @elseif($bgType === 'aurora-gradient')
                        background: 
                            radial-gradient(ellipse 150% 80% at 10% 20%, {{ $grad1 }}0c 0%, transparent 50%),
                            radial-gradient(ellipse 100% 60% at 90% 30%, {{ $grad2 }}0a 0%, transparent 50%),
                            radial-gradient(ellipse 120% 80% at 50% 90%, {{ $grad3 }}08 0%, transparent 60%),
                            {{ $bgColor }};
                    @elseif($bgType === 'sunset-gradient')
                        background: linear-gradient(180deg, {{ $grad1 }}10 0%, {{ $grad1 }}08 35%, {{ $bgColor }} 100%);
                    @elseif($bgType === 'ocean-gradient')
                        background: 
                            radial-gradient(ellipse 100% 50% at 50% 0%, {{ $grad1 }}0c 0%, transparent 60%),
                            radial-gradient(ellipse 100% 50% at 50% 100%, {{ $grad2 }}0a 0%, transparent 60%),
                            {{ $bgColor }};
                    @elseif($bgType === 'spotlight-gradient')
                        background: radial-gradient(ellipse 80% 60% at 75% 25%, {{ $grad1 }}14 0%, {{ $grad1 }}08 40%, {{ $bgColor }} 70%);
                    @else
                        background-color: {{ $bgColor }};
                    @endif
                }
            @endif
        @endisset
    </style>
@endif

@php
    $containerWidth = null;
    if (!empty($colors['dark']['--container-width'])) {
        $containerWidth = $colors['dark']['--container-width'];
    } elseif (!empty($colors['light']['--container-width'])) {
        $containerWidth = $colors['light']['--container-width'];
    }
@endphp

@php
    $maxContentWidth = null;
    if (!empty($colors['dark']['--max-content-width'])) {
        $maxContentWidth = $colors['dark']['--max-content-width'];
    } elseif (!empty($colors['light']['--max-content-width'])) {
        $maxContentWidth = $colors['light']['--max-content-width'];
    }
@endphp

<style>
    @if($containerWidth === 'fullwidth')
    .container:not(.keep-container) {
        max-width: none !important;
        width: 100% !important;
    }
    @elseif($maxContentWidth)
    .container:not(.keep-container) {
        max-width: {{ $maxContentWidth }} !important;
    }
    @endif
</style>
<script>
    document.documentElement.setAttribute('data-container-width', '{{ $containerWidth ?? 'container' }}');
</script>

@php
    // Get customization settings from colors.json
    $themeKey = config('app.change_theme', true)
        ? cookie()->get('theme', config('app.default_theme', 'dark'))
        : config('app.default_theme', 'dark');
    $themeColors = $colors[$themeKey] ?? [];
    
    $customizeSettings = [
        'nav_type' => $themeColors['--nav-type'] ?? 'horizontal',
        'nav_position' => $themeColors['--nav-position'] ?? 'top',
        'nav_blur' => ($themeColors['--nav-blur'] ?? 'true') === 'true',
        'footer_type' => $themeColors['--footer-type'] ?? 'default',
        'footer_socials' => ($themeColors['--footer-socials'] ?? 'true') === 'true',
        'animations' => ($themeColors['--animations'] ?? 'true') === 'true',
        'hover_scale' => ($themeColors['--hover-scale'] ?? 'true') === 'true',
        'content_align' => $themeColors['--content-align'] ?? 'left',
    ];
    
    $fontFamily = $themeColors['--font'] ?? null;
    $headingFont = $themeColors['--font-header'] ?? null;
    $fontsToLoad = [];
    
    // Extract font name from CSS value like "'Inter', sans-serif"
    if ($fontFamily && !str_contains($fontFamily, 'Manrope') && !str_contains($fontFamily, 'system-ui')) {
        if (preg_match("/['\"]?([^'\"',]+)/", $fontFamily, $matches)) {
            $fontName = trim($matches[1]);
            if ($fontName && $fontName !== 'sans-serif' && $fontName !== 'serif') {
                $fontsToLoad[] = $fontName;
            }
        }
    }
    if ($headingFont && $headingFont !== 'inherit' && !str_contains($headingFont, 'var(')) {
        if (preg_match("/['\"]?([^'\"',]+)/", $headingFont, $matches)) {
            $fontName = trim($matches[1]);
            if ($fontName && $fontName !== 'sans-serif' && $fontName !== 'serif' && !in_array($fontName, $fontsToLoad)) {
                $fontsToLoad[] = $fontName;
            }
        }
    }
@endphp

@if(!empty($fontsToLoad))
    @foreach($fontsToLoad as $font)
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family={{ urlencode($font) }}:wght@400;500;600;700;800&display=swap">
    @endforeach
@endif

<style>
    :root {
        @if(isset($themeColors['--font']))
            --font: {{ $themeColors['--font'] }};
        @endif
        @if(isset($themeColors['--font-header']))
            --font-header: {{ $themeColors['--font-header'] }};
        @endif
        @if(isset($themeColors['--font-scale']))
            --font-scale: {{ $themeColors['--font-scale'] }};
        @endif
        @if(isset($themeColors['--space-xs']))
            --space-xs: {{ $themeColors['--space-xs'] }};
        @endif
        @if(isset($themeColors['--space-sm']))
            --space-sm: {{ $themeColors['--space-sm'] }};
        @endif
        @if(isset($themeColors['--space-md']))
            --space-md: {{ $themeColors['--space-md'] }};
        @endif
        @if(isset($themeColors['--space-lg']))
            --space-lg: {{ $themeColors['--space-lg'] }};
        @endif
        @if(isset($themeColors['--space-xl']))
            --space-xl: {{ $themeColors['--space-xl'] }};
        @endif
        @if(isset($themeColors['--transition']))
            --transition: {{ $themeColors['--transition'] }};
        @endif
        @if(isset($themeColors['--blur-amount']))
            --blur-amount: {{ $themeColors['--blur-amount'] }};
        @endif
        @if(isset($themeColors['--max-content-width']))
            --max-content-width: {{ $themeColors['--max-content-width'] }};
        @endif
        @if(isset($themeColors['--shadow-small']))
            --shadow-small: {{ $themeColors['--shadow-small'] }};
        @endif
        @if(isset($themeColors['--shadow-medium']))
            --shadow-medium: {{ $themeColors['--shadow-medium'] }};
        @endif
        @if(isset($themeColors['--shadow-large']))
            --shadow-large: {{ $themeColors['--shadow-large'] }};
        @endif
    }
</style>

<script>
    (function() {
        var root = document.documentElement;
        root.setAttribute('data-nav-type', '{{ $customizeSettings['nav_type'] }}');
        root.setAttribute('data-nav-position', '{{ $customizeSettings['nav_position'] }}');
        root.setAttribute('data-nav-blur', '{{ $customizeSettings['nav_blur'] ? 'true' : 'false' }}');
        root.setAttribute('data-footer-type', '{{ $customizeSettings['footer_type'] }}');
        root.setAttribute('data-footer-socials', '{{ $customizeSettings['footer_socials'] ? 'true' : 'false' }}');
        root.setAttribute('data-animations', '{{ $customizeSettings['animations'] ? 'true' : 'false' }}');
        root.setAttribute('data-hover-scale', '{{ $customizeSettings['hover_scale'] ? 'true' : 'false' }}');
        root.setAttribute('data-content-align', '{{ $customizeSettings['content_align'] }}');
    })();
</script>
