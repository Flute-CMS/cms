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
                    $accentColor = $colors['dark']['--accent'] ?? '#A5FF75';
                    $primaryColor = $colors['dark']['--primary'] ?? '#f2f2f7';
                @endphp

                html[data-theme="dark"] body {
                    @if($bgType === 'linear-gradient')
                        background: linear-gradient(135deg, {{ $bgColor }} 0%, {{ $accentColor }}12 100%);
                    @elseif($bgType === 'radial-gradient')
                        background: radial-gradient(ellipse at top right, {{ $bgColor }} 0%, {{ $accentColor }}08 70%, {{ $bgColor }} 100%);
                    @elseif($bgType === 'mesh-gradient')
                        background: 
                            radial-gradient(at 20% 20%, {{ $accentColor }}08 0px, transparent 40%),
                            radial-gradient(at 80% 80%, {{ $primaryColor }}06 0px, transparent 40%),
                            radial-gradient(at 40% 70%, {{ $accentColor }}04 0px, transparent 40%),
                            {{ $bgColor }};
                    @elseif($bgType === 'subtle-gradient')
                        background: linear-gradient(160deg, {{ $bgColor }} 0%, {{ $accentColor }}06 50%, {{ $primaryColor }}04 100%);
                    @elseif($bgType === 'aurora-gradient')
                        background: linear-gradient(45deg, 
                            {{ $accentColor }}15 0%, 
                            {{ $primaryColor }}10 25%, 
                            {{ $accentColor }}20 50%, 
                            {{ $primaryColor }}15 75%, 
                            {{ $accentColor }}10 100%),
                            {{ $bgColor }};
                    @elseif($bgType === 'sunset-gradient')
                        background: linear-gradient(180deg, 
                            {{ $accentColor }}20 0%, 
                            {{ $accentColor }}15 30%, 
                            {{ $primaryColor }}10 70%, 
                            {{ $bgColor }} 100%);
                    @elseif($bgType === 'ocean-gradient')
                        background: 
                            radial-gradient(ellipse at top, {{ $accentColor }}08 0%, transparent 50%),
                            radial-gradient(ellipse at bottom, {{ $primaryColor }}06 0%, transparent 50%),
                            linear-gradient(180deg, {{ $bgColor }} 0%, {{ $accentColor }}04 100%);
                    @elseif($bgType === 'spotlight-gradient')
                        background: radial-gradient(circle at 70% 30%, 
                            {{ $accentColor }}12 0%, 
                            {{ $accentColor }}06 30%, 
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
                    $accentColor = $colors['light']['--accent'] ?? '#34c759';
                    $primaryColor = $colors['light']['--primary'] ?? '#1d1d1f';
                @endphp
                
                html[data-theme="light"] body {
                    @if($bgType === 'linear-gradient')
                        background: linear-gradient(135deg, {{ $bgColor }} 0%, {{ $accentColor }}08 100%);
                    @elseif($bgType === 'radial-gradient')
                        background: radial-gradient(ellipse at top right, {{ $bgColor }} 0%, {{ $accentColor }}05 70%, {{ $bgColor }} 100%);
                    @elseif($bgType === 'mesh-gradient')
                        background: 
                            radial-gradient(at 20% 20%, {{ $accentColor }}04 0px, transparent 40%),
                            radial-gradient(at 80% 80%, {{ $primaryColor }}03 0px, transparent 40%),
                            radial-gradient(at 40% 70%, {{ $accentColor }}02 0px, transparent 40%),
                            {{ $bgColor }};
                    @elseif($bgType === 'subtle-gradient')
                        background: linear-gradient(160deg, {{ $bgColor }} 0%, {{ $accentColor }}04 50%, {{ $primaryColor }}02 100%);
                    @elseif($bgType === 'aurora-gradient')
                        background: linear-gradient(45deg, 
                            {{ $accentColor }}08 0%, 
                            {{ $primaryColor }}05 25%, 
                            {{ $accentColor }}10 50%, 
                            {{ $primaryColor }}08 75%, 
                            {{ $accentColor }}06 100%),
                            {{ $bgColor }};
                    @elseif($bgType === 'sunset-gradient')
                        background: linear-gradient(180deg, 
                            {{ $accentColor }}12 0%, 
                            {{ $accentColor }}08 30%, 
                            {{ $primaryColor }}05 70%, 
                            {{ $bgColor }} 100%);
                    @elseif($bgType === 'ocean-gradient')
                        background: 
                            radial-gradient(ellipse at top, {{ $accentColor }}04 0%, transparent 50%),
                            radial-gradient(ellipse at bottom, {{ $primaryColor }}03 0%, transparent 50%),
                            linear-gradient(180deg, {{ $bgColor }} 0%, {{ $accentColor }}02 100%);
                    @elseif($bgType === 'spotlight-gradient')
                        background: radial-gradient(circle at 70% 30%, 
                            {{ $accentColor }}06 0%, 
                            {{ $accentColor }}03 30%, 
                            {{ $bgColor }} 70%);
                    @else
                        background-color: {{ $bgColor }};
                    @endif
                }
            @endif
        @endisset
    </style>
@endif
