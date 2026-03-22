<div class="d-flex flex-column">
    <span style="font-weight: 500;">{{ $description ?? __('profile.edit.balance_history.no_description') }}</span>
    @if ($source)
        <small style="color: var(--text-500);">{{ $source }}</small>
    @endif
</div>
