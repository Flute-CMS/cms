<div class="dashboard-notices"  hx-boost="true" hx-target="#main" yoyo:ignore>
    {{-- Attention pills --}}
    @if ($attention->hasItems())
        <div class="dashboard-notices__alerts">
            @foreach ($attentionItems as $item)
                <a href="{{ $item['url'] }}" class="dashboard-notices__pill dashboard-notices__pill--{{ $item['type'] }}">
                    <x-icon path="{{ $item['icon'] }}" />
                    <span>@lang("admin-dashboard.attention.{$item['key']}", ['count' => $item['count'] ?? 0])</span>
                    <x-icon path="ph.bold.arrow-right-bold" class="dashboard-notices__pill-arrow" />
                </a>
            @endforeach
        </div>
    @endif

    {{-- Setup checklist --}}
    @if (!$checklist->isAllDone())
        <div class="dashboard-notices__checklist">
            <div class="dashboard-notices__checklist-head">
                <span class="dashboard-notices__checklist-counter">{{ $checklist->getCompletedCount() }}/{{ $checklist->getTotalCount() }}</span>
                <div class="dashboard-notices__checklist-bar">
                    <div class="dashboard-notices__checklist-bar-fill" style="width: {{ $checklist->getProgressPercent() }}%"></div>
                </div>
            </div>
            @foreach ($checklistItems as $item)
                <a href="{{ $item['url'] }}" class="dashboard-notices__checklist-row {{ $item['done'] ? 'is-done' : '' }}">
                    <span class="dashboard-notices__check">
                        @if ($item['done'])
                            <x-icon path="ph.bold.check-bold" />
                        @endif
                    </span>
                    <span class="dashboard-notices__label">@lang("admin-dashboard.checklist.items.{$item['key']}.title")</span>
                    <span class="dashboard-notices__hint">@lang("admin-dashboard.checklist.items.{$item['key']}.desc")</span>
                    <x-icon path="ph.bold.caret-right-bold" class="dashboard-notices__caret" />
                </a>
            @endforeach
        </div>
    @endif
</div>
