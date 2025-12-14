<x-forms.field class="table__search" style="min-width: 300px">
    <x-fields.input
        name="table-search"
        id="table-search-{{ $tableId ?? 'default' }}"
        value="{{ $searchValue ?? '' }}"
        placeholder="{{ __('def.lets_search') }}"
        data-ignore-dirty="true"
        autocomplete="off"
        hx-get="render"
        hx-trigger="input delay:500ms"
        hx-target="#screen-container"
        hx-swap="innerHTML"
        hx-include="this"
    />
</x-forms.field>
