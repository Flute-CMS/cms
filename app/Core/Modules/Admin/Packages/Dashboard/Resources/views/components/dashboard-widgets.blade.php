<div class="row gx-3 gy-3 mt-0" yoyo:ignore>
    {{-- Recent registrations --}}
    <div class="col-md-6">
        <div class="dash-widget">
            <div class="dash-widget__header">
                <x-icon path="ph.bold.users-bold" class="dash-widget__header-icon" />
                <span>{{ __('admin-dashboard.widgets.recent_users') }}</span>
            </div>
            <div class="dash-widget__body" hx-boost="true" hx-target="#main" hx-swap="morph:outerHTML transition:true">
                @forelse ($recentUsers as $u)
                    <a href="{{ url('/admin/users/' . $u->id . '/edit') }}" class="dash-widget__row">
                        <img src="{{ $u->avatar ?: asset('assets/img/no_avatar.webp') }}" alt="" class="dash-widget__avatar" loading="lazy">
                        <div class="dash-widget__row-info">
                            <span class="dash-widget__row-title">{{ $u->name }}</span>
                            <span class="dash-widget__row-sub">{{ \Carbon\Carbon::parse($u->createdAt)->diffForHumans() }}</span>
                        </div>
                        <x-icon path="ph.bold.caret-right-bold" class="dash-widget__chevron" />
                    </a>
                @empty
                    <div class="dash-widget__empty">
                        <x-icon path="ph.regular.users" />
                        <span>{{ __('admin-dashboard.widgets.no_recent_users') }}</span>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- System info --}}
    <div class="col-md-6">
        <div class="dash-widget">
            <div class="dash-widget__header">
                <x-icon path="ph.bold.gear-bold" class="dash-widget__header-icon" />
                <span>{{ __('admin-dashboard.widgets.system_info') }}</span>
            </div>
            <div class="dash-widget__body">
                <div class="dash-widget__kv">
                    <span class="dash-widget__kv-label">Flute</span>
                    <span class="dash-widget__kv-value">{{ config('app.version', '?') }}</span>
                </div>
                <div class="dash-widget__kv">
                    <span class="dash-widget__kv-label">PHP</span>
                    <span class="dash-widget__kv-value">{{ PHP_VERSION }}</span>
                </div>
                <div class="dash-widget__kv">
                    <span class="dash-widget__kv-label">{{ __('admin-dashboard.system.database') }}</span>
                    <span class="dash-widget__kv-value">{{ $dbDriver }}</span>
                </div>
                <div class="dash-widget__kv">
                    <span class="dash-widget__kv-label">{{ __('admin-dashboard.system.cache_driver') }}</span>
                    <span class="dash-widget__kv-value">{{ ucfirst($cacheDriver) }}</span>
                </div>
                <div class="dash-widget__kv">
                    <span class="dash-widget__kv-label">{{ __('admin-dashboard.system.timezone') }}</span>
                    <span class="dash-widget__kv-value">{{ config('app.timezone', 'UTC') }}</span>
                </div>
                <div class="dash-widget__kv">
                    <span class="dash-widget__kv-label">{{ __('admin-dashboard.system.debug_mode') }}</span>
                    <span class="dash-widget__kv-badge {{ config('app.debug') ? 'dash-widget__kv-badge--warn' : 'dash-widget__kv-badge--ok' }}">
                        {{ config('app.debug') ? 'ON' : 'OFF' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
