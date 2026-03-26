<div class="profile-payments">
    {{-- Balance overview --}}
    <div class="profile-payments__overview">
        <div class="profile-payments__balance">
            <div class="profile-payments__balance-label">{{ __('profile.edit.payments.balance') }}</div>
            <div class="profile-payments__balance-value" data-profile-balance>
                {{ number_format($user->balance, 0) }} {{ config('lk.currency_view') }}
            </div>
        </div>

        @if (config('lk.only_modal'))
            <x-button type="primary" size="small" data-modal-open="lk-modal">
                <x-icon path="ph.regular.plus" />
                {{ __('profile.edit.payments.top_up') }}
            </x-button>
        @else
            <x-button type="primary" size="small" href="{{ url('/lk') }}">
                <x-icon path="ph.regular.plus" />
                {{ __('profile.edit.payments.top_up') }}
            </x-button>
        @endif
    </div>

    {{-- Payment invoices --}}
    <div class="profile-payments__section">
        <div class="profile-payments__section-header">
            <h5>{{ __('profile.edit.payments.invoices_title') }}</h5>
        </div>
        <x-card withoutPadding>
            @yoyo('table-payments')
        </x-card>
    </div>

    {{-- Extension point: modules can @push('profile-purchases') to add their own sections --}}
    @stack('profile-purchases')
</div>
