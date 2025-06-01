@if (! empty(footer()->all()))
    @foreach (footer()->all() as $key => $item)
        <div class="footer__col" itemscope itemtype="https://schema.org/SiteNavigationElement">
            <h6 class="footer__title">
                @if (isset($item['url']) && ! empty($item['url']))
                    <a href="{{ $item['url'] }}" @if ($item['new_tab']) target="_blank" rel="noopener" @endif itemprop="name">
                        {!! __($item['title']) !!}
                    </a>
                @else
                    <span itemprop="name">{!! __($item['title']) !!}</span>
                @endif
            </h6>

            <nav aria-label="{{ __($item['title']) }} navigation">
                <ul class="footer__items">
                    @foreach ($item['children'] as $child_item)
                        <li class="footer__items-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/SiteNavigationElement">
                            <a href="{{ $child_item['url'] }}" @if ($child_item['new_tab']) target="_blank" rel="noopener" @endif 
                               itemprop="url" aria-label="{!! __($child_item['title']) !!}">
                                <x-icon path="ph.bold.arrow-up-right-bold" aria-hidden="true" />
                                <span itemprop="name">{!! __($child_item['title']) !!}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>
    @endforeach
@endif