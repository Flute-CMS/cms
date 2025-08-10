<nav class="breadcrumb" hx-swap-oob="innerHTML:#breadcrumb-container">
    <ul class="breadcrumb-links" hx-boost="true" hx-target="#main" hx-swap="morph:outerHTML transition:true">
        @foreach (breadcrumb()->all() as $index => $crumb)
            <li>
                @if ($index > 0)
                    <div class="breadcrumb-box">
                        <span class="breadcrumb-icon">/</span>

                        @if ($crumb['url'])
                            <a href="{{ $crumb['url'] }}" class="breadcrumb-text">{{ $crumb['title'] }}</a>
                        @else
                            <span class="breadcrumb-text">{{ $crumb['title'] }}</span>
                        @endif
                    </div>
                @else
                    <a href="{{ $crumb['url'] ? $crumb['url'] : '#' }}" class="breadcrumb-box">
                        <span class="breadcrumb-text">{{ $crumb['title'] }}</span>
                    </a>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
