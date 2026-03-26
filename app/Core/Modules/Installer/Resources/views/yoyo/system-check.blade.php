@php
    $totalChecks = count($extensionRequirements) + count($directoryRequirements) + count($phpRequirements);
    $passedChecks = 0;
    foreach ($phpRequirements as $r) { if ($r['status']) $passedChecks++; }
    foreach ($extensionRequirements as $r) { if ($r['status']) $passedChecks++; }
    foreach ($directoryRequirements as $r) { if ($r['status']) $passedChecks++; }

    $extNames = array_map(fn($r) => $r['name'], array_filter($extensionRequirements, fn($r) => $r['status']));
    $extSummary = count($extNames) > 5
        ? implode(', ', array_slice($extNames, 0, 5)) . ' +' . (count($extNames) - 5)
        : implode(', ', $extNames);

    $dirNames = array_map(fn($r) => str_replace(['Directory: ', 'Директория: '], '', $r['name']), array_filter($directoryRequirements, fn($r) => $r['status']));
    $dirSummary = count($dirNames) > 3
        ? implode(', ', array_slice($dirNames, 0, 3)) . '…'
        : implode(', ', $dirNames);
@endphp
<div class="system-check-step">
    <div class="step-panel">
        <div class="step-header">
            <div class="step-header__icon step-header__icon--blue">
                <x-icon path="ph.regular.shield-check" />
            </div>
            <h1>{{ __('install.system_check.heading') }}</h1>
            <p class="step-subtitle">{{ __('install.system_check.subtitle') }}</p>
        </div>

        <div class="step-body">
            <div class="status-bar {{ $allRequirementsMet ? 'status-bar--ok' : 'status-bar--fail' }}">
                <span class="status-bar__dot"></span>
                <span class="status-bar__text">{{ $allRequirementsMet ? __('install.system_check.all_met') : __('install.system_check.not_met') }}</span>
                <span class="status-bar__count">{{ $passedChecks }} / {{ $totalChecks }}</span>
            </div>

            <div class="check-summary">
                @foreach($phpRequirements as $req)
                    <div class="check-row">
                        <div class="check-row__icon check-row__icon--{{ $req['status'] ? 'ok' : 'fail' }}">
                            @if($req['status'])
                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                            @else
                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
                            @endif
                        </div>
                        <span class="check-row__label">{{ __('install.system_check.php_version') }}</span>
                        <span class="check-row__value">{{ $req['current'] }}</span>
                        <span class="check-row__hint">≥ {{ $req['required'] }}</span>
                    </div>
                @endforeach

                <div class="check-row">
                    <div class="check-row__icon check-row__icon--{{ collect($extensionRequirements)->every('status', true) ? 'ok' : 'fail' }}">
                        @if(collect($extensionRequirements)->every('status', true))
                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                        @else
                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
                        @endif
                    </div>
                    <span class="check-row__label">{{ __('install.system_check.extensions') }}</span>
                    <span class="check-row__hint">{{ $extSummary }}</span>
                </div>

                <div class="check-row">
                    <div class="check-row__icon check-row__icon--{{ collect($directoryRequirements)->every('status', true) ? 'ok' : 'fail' }}">
                        @if(collect($directoryRequirements)->every('status', true))
                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                        @else
                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
                        @endif
                    </div>
                    <span class="check-row__label">{{ __('install.system_check.directories') }}</span>
                    <span class="check-row__hint">{{ $dirSummary }}</span>
                </div>
            </div>

            <button type="button" class="expand-btn">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                {{ __('install.system_check.show_all') }} ({{ $totalChecks }})
            </button>

            <div id="checkDetails">
                <div class="detail-grid">
                    @foreach($extensionRequirements as $req)
                        <div class="detail-row">
                            <span class="detail-check detail-check--{{ $req['status'] ? 'ok' : 'fail' }}">{{ $req['status'] ? '✓' : '✗' }}</span>
                            {{ $req['name'] }}
                        </div>
                    @endforeach
                    @foreach($directoryRequirements as $req)
                        <div class="detail-row">
                            <span class="detail-check detail-check--{{ $req['status'] ? 'ok' : 'fail' }}">{{ $req['status'] ? '✓' : '✗' }}</span>
                            {{ str_replace(['Directory: ', 'Директория: '], '', $req['name']) }}
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="check-ioncube {{ $ionCubeCheck['status'] ? 'check-ioncube--ok' : 'check-ioncube--warn' }}">
                <div class="check-ioncube__icon">
                    @if($ionCubeCheck['status'])
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                    @else
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 9v4m0 4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/></svg>
                    @endif
                </div>
                <div class="check-ioncube__text">
                    @if($ionCubeCheck['status'])
                        {{ __('install.system_check.ioncube_ok') }}
                    @else
                        {{ __('install.system_check.ioncube_warn') }}
                    @endif
                </div>
            </div>

            @if(! $allRequirementsMet)
                <div class="alert alert--danger" style="margin-top: 16px;">
                    {{ __('install.system_check.fix_errors') }}
                </div>
            @endif
        </div>

        <div class="step-footer">
            <div class="installer-form__actions">
                <a href="{{ route('installer.welcome') }}" class="btn btn--link" hx-boost="true">
                    <span class="btn__label">
                        <x-icon path="ph.regular.caret-left" />
                        {{ __('install.common.back') }}
                    </span>
                </a>
                <button
                    class="btn btn--primary"
                    hx-get="{{ route('installer.step', ['id' => 2]) }}"
                    hx-target="body"
                    hx-swap="morph"
                    hx-push-url="true"
                    {{ ! $allRequirementsMet ? 'disabled' : '' }}
                >
                    <span class="btn__spinner"></span>
                    <span class="btn__label">
                        {{ __('install.common.next') }}
                        <x-icon path="ph.regular.arrow-right" />
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
