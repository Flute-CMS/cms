<div class="container">
    <div class="row">
        <div class="col-md-12">
            <x-alert type="warning" onlyBorders withClose="false">
                {{ __('def.debug_message') }}
                @if (is_development())
                    <br><small>(development mode)</small>
                @endif
            </x-alert>
        </div>
    </div>
</div>
