@if($isConnected)
    <div class="test-ok">
        <x-icon path="ph.regular.check-circle" />
        {{ __('install.database.connection_success') }}
    </div>
@else
    @if($errorMessage)
        <div class="alert alert--danger" style="margin-bottom: 10px;">
            {{ $errorMessage }}
        </div>
    @endif

    <button type="submit" class="test-btn">
        <span class="test-btn__spinner"></span>
        <span class="test-btn__label">
            <x-icon path="ph.regular.plug" />
            {{ $errorMessage ? __('install.database.retry_connection') : __('install.database.test_connection') }}
        </span>
    </button>
@endif
