@if (count($results) > 0)
    <div class="search-results-count">
        <span>{{ __('search.search_results_for', ['%query%' => $query]) }}:</span>
    </div>
    <ul class="search-results-list">
        @foreach ($results as $result)
            <li class="search-result-item" role="option" aria-selected="false">
                <a href="{{ $result->getUrl() }}" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true"
                    class="flex items-center">
                    @if ($result->getIcon())
                        @if(\Nette\Utils\Validators::isUrl($result->getIcon()))
                            <img src="{{ $result->getIcon() }}" alt="{{ __($result->getTitle()) }}" class="search-image">
                        @else
                            <x-icon :path="$result->getIcon()" />
                        @endif
                    @endif
                    <span>{{ __($result->getTitle()) }}</span>
                    <x-icon path="ph.regular.arrow-right" class="search-go-icon" />
                </a>
            </li>
        @endforeach
    </ul>
@else
    <p class="text-muted">{{ __('search.no_results_found') }}</p>
@endif
