<x-modal id="search-dialog" :withoutCloseButton="true" contentClass="search-dialog__content" data-ignore-overflow>
    <div class="search-dialog__container">
        <div class="search-dialog__header">
            <x-icon path="ph.regular.magnifying-glass" class="search-dialog__search-icon" />
            <form class="search-dialog__form" onsubmit="return false;">
                <input type="text" name="query" class="search-dialog__input"
                    placeholder="{{ __('search.placeholder') }}" autocomplete="off" autocapitalize="off"
                    autocorrect="off" spellcheck="false">
            </form>
            <div id="search-spinner" class="search-dialog__spinner">
                <span></span>
            </div>
            <button type="button" class="search-dialog__esc" data-close-search>Esc</button>
        </div>

        <div class="search-dialog__body">
            <div id="command-suggestions" class="search-commands search-section--hidden"></div>
            <div id="search-results" class="search-results search-section--hidden"></div>

            <div id="search-empty" class="search-empty">
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
            </div>
        </div>

        <div class="search-dialog__footer">
            <div class="search-dialog__hints">
                <span class="search-dialog__hint">
                    <kbd><x-icon path="ph.bold.arrow-up-bold" /></kbd>
                    <kbd><x-icon path="ph.bold.arrow-down-bold" /></kbd>
                    {{ __('search.hint_navigate') }}
                </span>
                <span class="search-dialog__hint">
                    <kbd><x-icon path="ph.bold.arrow-elbow-down-left-bold" /></kbd>
                    {{ __('search.hint_select') }}
                </span>
                <span class="search-dialog__hint">
                    <kbd>/</kbd>
                    {{ __('search.hint_commands') }}
                </span>
            </div>
        </div>
    </div>
</x-modal>
