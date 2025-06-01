<x-modal id="search-dialog" :withoutCloseButton="true" contentClass="search-dialog__content" data-ignore-overflow>
    <div class="search-dialog__container">
        <form class="w-full"
            onsubmit="return false;">
            <input type="text" name="query" class="search-dialog__input" placeholder="{{ __('search.lets_search') }}"
                hx-get="{{ url('admin/search') }}" hx-trigger="keyup changed delay:50ms" hx-target="#search-results" hx-swap="innerHTML" autocomplete="off"
                aria-label="{{ __('search.search_input') }}" aria-controls="search-results" aria-expanded="false" data-noprogress>
        </form>
        <div id="command-suggestions" class="command-suggestions search-results--hidden" role="listbox"></div>
        <div id="search-results" class="search-results search-results--hidden" role="listbox"></div>
    </div>
</x-modal>