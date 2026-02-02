<div class="search-empty__tips">
    <div class="search-tips__title">{{ __('search.quick_actions') }}</div>
    <ul class="search-tips__list">
        <li class="search-tips__item" data-search-tip="/user">
            <x-icon path="ph.regular.users" />
            <span class="search-tips__command">/user</span>
            <span class="search-tips__sep">—</span>
            <span class="search-tips__desc">{{ __('search.tip_users') }}</span>
        </li>
        <li class="search-tips__item" data-search-tip="/settings">
            <x-icon path="ph.regular.gear" />
            <span class="search-tips__command">/settings</span>
            <span class="search-tips__sep">—</span>
            <span class="search-tips__desc">{{ __('search.tip_settings') }}</span>
        </li>
        <li class="search-tips__item" data-search-tip="/page">
            <x-icon path="ph.regular.file-text" />
            <span class="search-tips__command">/page</span>
            <span class="search-tips__sep">—</span>
            <span class="search-tips__desc">{{ __('search.tip_pages') }}</span>
        </li>
        <li class="search-tips__item" data-search-tip="/server">
            <x-icon path="ph.regular.hard-drives" />
            <span class="search-tips__command">/server</span>
            <span class="search-tips__sep">—</span>
            <span class="search-tips__desc">{{ __('search.tip_servers') }}</span>
        </li>
    </ul>
</div>
