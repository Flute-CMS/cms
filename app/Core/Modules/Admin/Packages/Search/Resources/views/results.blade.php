@if (count($results) > 0)
    @php
        $groupedResults = [];
        foreach ($results as $result) {
            $category = $result->getCategory() ?: __('search.category_other');
            if (!isset($groupedResults[$category])) {
                $groupedResults[$category] = [];
            }
            $groupedResults[$category][] = $result;
        }
    @endphp

    @foreach ($groupedResults as $category => $categoryResults)
        <div class="search-group">
            <div class="search-group__header">
                <span class="search-group__title">{{ $category }}</span>
                <span class="search-group__count">{{ count($categoryResults) }}</span>
            </div>
            <ul class="search-group__list">
                @foreach ($categoryResults as $result)
                    <li class="search-result-item">
                        <a href="{{ $result->getUrl() }}">
                            <div class="search-result-item__icon">
                                @if ($result->getIcon())
                                    @if (\Nette\Utils\Validators::isUrl($result->getIcon()))
                                        <img src="{{ $result->getIcon() }}" alt="{{ __($result->getTitle()) }}">
                                    @else
                                        <x-icon :path="$result->getIcon()" />
                                    @endif
                                @else
                                    <x-icon path="ph.regular.file" />
                                @endif
                            </div>
                            <div class="search-result-item__content">
                                <span class="search-result-item__title">{!! $result->getHighlightedTitle($query ?? '') !!}</span>
                            </div>
                            <x-icon path="ph.regular.arrow-right" class="search-result-item__arrow" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
@else
    <div class="search-no-results">
        <x-icon path="ph.regular.magnifying-glass" />
        <p>{{ __('search.no_results') }}</p>
        <span>{{ __('search.try_different') }}</span>
    </div>
@endif
