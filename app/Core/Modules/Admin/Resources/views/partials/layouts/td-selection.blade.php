<td class="text-center row-selector selection-col" style="width:36px;min-width:36px;padding-right:10px;" @if (isset($aria_hidden)) aria-hidden="{{ $aria_hidden }}" @endif>
    <div class="checkbox-wrapper checkbox--compact">
        <div class="checkbox__field-container">
            <input type="checkbox" name="selected[]" value="{{ $value }}" id="select-row-{{ $tableId ?? 'tbl' }}-{{ $value }}" class="checkbox__field" />
            <label for="select-row-{{ $tableId ?? 'tbl' }}-{{ $value }}" class="checkbox__label"></label>
        </div>
    </div>
</td>
