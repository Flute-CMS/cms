<td class="text-center row-selector selection-col" style="width:36px;min-width:36px;padding-right:10px;" @if (isset($aria_hidden)) aria-hidden="{{ $aria_hidden }}" @endif>
    <x-fields.checkbox name="selected[]" value="{{ $value }}" :id="'select-row-' . ($tableId ?? 'tbl') . '-' . $value" />
</td>


