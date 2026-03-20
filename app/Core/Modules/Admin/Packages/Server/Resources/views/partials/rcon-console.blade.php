<div class="rcon">
    <div class="rcon__output" id="rcon-output">
        @if (empty($history))
            <div class="rcon__empty">{{ __('admin-server.rcon.empty') }}</div>
        @else
            @foreach ($history as $entry)
                <div class="rcon__entry">
                    <div class="rcon__cmd">> {{ $entry['cmd'] }}</div>
                    <pre class="rcon__result {{ $entry['ok'] ? '' : 'rcon__result--err' }}">{{ $entry['out'] }}</pre>
                </div>
            @endforeach
        @endif
    </div>

    <div class="rcon__bar">
        @if (!empty($history))
            <button type="button" yoyo:post="clearRcon" class="rcon__clear" title="{{ __('def.clear') ?? 'Clear' }}">
                <x-icon path="ph.bold.trash-bold" />
            </button>
        @endif
        <input
            type="text"
            name="rcon_command"
            class="rcon__field"
            placeholder="{{ __('admin-server.rcon.placeholder') }}"
            autocomplete="off"
            autofocus
        />
        <button type="button" yoyo:post="executeRcon" class="rcon__send">
            <x-icon path="ph.bold.paper-plane-right-bold" />
        </button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const output = document.getElementById('rcon-output');
        if (output) output.scrollTop = output.scrollHeight;

        const field = document.querySelector('.rcon__field');
        if (field) {
            field.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.querySelector('.rcon__send')?.click();
                }
            });
        }
    });
</script>
