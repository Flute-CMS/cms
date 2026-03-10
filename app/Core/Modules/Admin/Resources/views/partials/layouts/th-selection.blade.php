<th class="text-center selection-col" style="width:36px;min-width:36px;padding-right:10px;" @if (isset($aria_hidden)) aria-hidden="{{ $aria_hidden }}" @endif>
    <div class="checkbox-wrapper checkbox--compact">
        <div class="checkbox__field-container">
            <input type="checkbox" id="select-all-{{ $tableId }}" class="checkbox__field table-select-all" />
            <label for="select-all-{{ $tableId }}" class="checkbox__label"></label>
        </div>
    </div>
</th>

