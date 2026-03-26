<div class="ranks-card__header">
    <div>
        <div class="ranks-card__title">{{ __('admin-server.ranks_section.title') }}</div>
        <div class="ranks-card__desc">{{ __('admin-server.ranks_section.description') }}</div>
    </div>
    <div class="ranks-card__toggle">
        <x-fields.buttongroup
            name="ranks_premier"
            :options="[
                '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.crown-bold'],
            ]"
            :value="$premierValue ?? '0'"
            color="accent"
            size="small"
            :yoyo="true"
        />
    </div>
</div>
