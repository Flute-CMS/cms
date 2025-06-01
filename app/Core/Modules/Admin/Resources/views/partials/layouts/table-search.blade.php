<x-forms.field class="table__search" style="min-width: 300px" yoyo yoyo:on="input changed delay:500ms"
    hx-swap="morph:outerHTML transition:true">
    <x-fields.input name="table-search" id="table-search" value="{{ $searchValue ?? '' }}"
        placeholder="{{ __('def.lets_search') }}" />
</x-forms.field>
