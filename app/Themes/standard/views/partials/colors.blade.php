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
                        background: 
                            linear-gradient(135deg, {{ $bgColor }} 0%, {{ $bgColor }} 45%, {{ $grad1 }}18 100%),
                            radial-gradient(1200px circle at 90% -10%, {{ $grad1 }}0f 0%, transparent 60%),
                            radial-gradient(800px circle at 10% 110%, {{ $grad2 }}0c 0%, transparent 60%),
                            {{ $bgColor }};
                    @elseif($bgType === 'radial-gradient')
                        background: 
                            radial-gradient(1000px circle at 30% 10%, {{ $grad1 }}14 0%, transparent 55%),
                            radial-gradient(1200px circle at 82% 78%, {{ $grad2 }}0f 0%, transparent 60%),
                            {{ $bgColor }};
                    @elseif($bgType === 'mesh-gradient')
                        background: 
                            radial-gradient(at 20% 20%, {{ $grad1 }}12 0px, transparent 45%),
                            radial-gradient(at 80% 75%, {{ $grad2 }}0d 0px, transparent 45%),
                            radial-gradient(at 40% 70%, {{ $grad1 }}0a 0px, transparent 40%),
                            radial-gradient(at 70% 30%, {{ $grad2 }}08 0px, transparent 45%),
                            {{ $bgColor }};
                    @elseif($bgType === 'subtle-gradient')
                        background: linear-gradient(160deg, {{ $bgColor }} 0%, {{ $grad1 }}0d 50%, {{ $grad2 }}0a 100%);
                    @elseif($bgType === 'aurora-gradient')
                        background: 
                            radial-gradient(1200px circle at 10% 20%, {{ $grad1 }}12 0%, transparent 55%),
                            radial-gradient(1000px circle at 80% 30%, {{ $grad2 }}10 0%, transparent 55%),
                            radial-gradient(1400px circle at 50% 80%, {{ $grad3 }}0d 0%, transparent 60%),
                            {{ $bgColor }};
                    @elseif($bgType === 'sunset-gradient')
                        background: linear-gradient(180deg, 
                            {{ $grad1 }}18 0%, 
                            {{ $grad1 }}10 28%, 
                            {{ $grad2 }}0d 68%, 
                            {{ $bgColor }} 100%);
                    @elseif($bgType === 'ocean-gradient')
                        background: 
                            radial-gradient(900px ellipse at top, {{ $grad1 }}10 0%, transparent 50%),
                            radial-gradient(700px ellipse at bottom, {{ $grad2 }}0d 0%, transparent 50%),
                            linear-gradient(180deg, {{ $bgColor }} 0%, {{ $grad3 }}06 100%);
                    @elseif($bgType === 'spotlight-gradient')
                        background: radial-gradient(800px circle at 70% 30%, 
                            {{ $grad1 }}20 0%, 
                            {{ $grad1 }}0d 28%, 
                            {{ $bgColor }} 70%);
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
                        background: 
                            linear-gradient(135deg, {{ $bgColor }} 0%, {{ $bgColor }} 45%, {{ $grad1 }}0f 100%),
                            radial-gradient(1000px circle at 92% -8%, {{ $grad1 }}0a 0%, transparent 58%),
                            radial-gradient(700px circle at 8% 106%, {{ $grad2 }}08 0%, transparent 58%),
                            {{ $bgColor }};
                    @elseif($bgType === 'radial-gradient')
                        background: 
                            radial-gradient(900px circle at 28% 12%, {{ $grad1 }}0d 0%, transparent 55%),
                            radial-gradient(1100px circle at 82% 78%, {{ $grad2 }}08 0%, transparent 60%),
                            {{ $bgColor }};
                    @elseif($bgType === 'mesh-gradient')
                        background: 
                            radial-gradient(at 20% 20%, {{ $grad1 }}0a 0px, transparent 45%),
                            radial-gradient(at 80% 75%, {{ $grad2 }}08 0px, transparent 45%),
                            radial-gradient(at 40% 70%, {{ $grad3 }}06 0px, transparent 40%),
                            radial-gradient(at 70% 30%, {{ $grad2 }}05 0px, transparent 45%),
                            {{ $bgColor }};
                    @elseif($bgType === 'subtle-gradient')
                        background: linear-gradient(160deg, {{ $bgColor }} 0%, {{ $grad1 }}08 50%, {{ $grad2 }}06 100%);
                    @elseif($bgType === 'aurora-gradient')
                        background: 
                            radial-gradient(1100px circle at 12% 22%, {{ $grad1 }}0a 0%, transparent 55%),
                            radial-gradient(900px circle at 82% 30%, {{ $grad2 }}08 0%, transparent 55%),
                            radial-gradient(1200px circle at 50% 80%, {{ $grad3 }}06 0%, transparent 60%),
                            {{ $bgColor }};
                    @elseif($bgType === 'sunset-gradient')
                        background: linear-gradient(180deg, 
                            {{ $grad1 }}12 0%, 
                            {{ $grad1 }}0c 30%, 
                            {{ $grad2 }}08 70%, 
                            {{ $bgColor }} 100%);
                    @elseif($bgType === 'ocean-gradient')
                        background: 
                            radial-gradient(800px ellipse at top, {{ $grad1 }}0c 0%, transparent 50%),
                            radial-gradient(650px ellipse at bottom, {{ $grad2 }}08 0%, transparent 50%),
                            linear-gradient(180deg, {{ $bgColor }} 0%, {{ $grad3 }}05 100%);
                    @elseif($bgType === 'spotlight-gradient')
                        background: radial-gradient(700px circle at 70% 30%, 
                            {{ $grad1 }}12 0%, 
                            {{ $grad1 }}08 30%, 
                            {{ $bgColor }} 70%);
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

@if($containerWidth === 'fullwidth')
    <style>
        .container:not(.keep-container) {
            max-width: none !important;
            width: 100% !important;
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }
    </style>
    <script>
        document.documentElement.setAttribute('data-container-width', 'fullwidth');
        if (typeof window !== 'undefined') {
            window.addEventListener('DOMContentLoaded', function() {
                const toggle = document.getElementById('container-width-checkbox');
                if (toggle) {
                    toggle.checked = true;
                    localStorage.setItem('container-width-mode', 'fullwidth');
                }
            });
        }
    </script>
@else
    <script>
        document.documentElement.setAttribute('data-container-width', 'container');
        if (typeof window !== 'undefined') {
            window.addEventListener('DOMContentLoaded', function() {
                const toggle = document.getElementById('container-width-checkbox');
                if (toggle) {
                    toggle.checked = false;
                    localStorage.setItem('container-width-mode', 'container');
                }
            });
        }
    </script>
@endif
