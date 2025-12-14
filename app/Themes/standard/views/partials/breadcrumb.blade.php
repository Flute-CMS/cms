@if (breadcrumb()->all())
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb navigation" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">
            <ul class="breadcrumb-links" itemscope itemtype="https://schema.org/BreadcrumbList">
                @foreach (breadcrumb()->all() as $index => $crumb)
                    <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                        @if ($index > 0)
                            <div class="breadcrumb-box">
                                <svg class="breadcrumb-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd"
                                        d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
                                        clip-rule="evenodd" />
                                </svg>

                                @if ($crumb['url'])
                                    <a href="{{ $crumb['url'] }}" class="breadcrumb-text" itemprop="item">
                                        <span itemprop="name">{{ $crumb['title'] }}</span>
                                    </a>
                                @else
                                    <span class="breadcrumb-text" itemprop="name">{{ $crumb['title'] }}</span>
                                @endif
                                <meta itemprop="position" content="{{ $index + 1 }}" />
                            </div>
                        @else
                            <a href="{{ $crumb['url'] ? $crumb['url'] : '#' }}" class="breadcrumb-box" itemprop="item">
                                <span itemprop="name" class="breadcrumb-text">{{ $crumb['title'] }}</span>
                                <meta itemprop="position" content="{{ $index + 1 }}" />
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>
    </div>
@endif
