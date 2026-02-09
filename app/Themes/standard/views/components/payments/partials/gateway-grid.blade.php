{{-- Render gateways for ALL currencies; JS shows only active currency --}}
@foreach ($currencyGateways as $currCode => $gateways)
    @php $isCurrent = $currCode === $currency; @endphp

    <div class="lk-methods {{ count($gateways) === 1 ? 'is-single' : '' }}"
        data-lk-gateways="{{ $currCode }}"
        role="radiogroup" aria-label="{{ __('lk.select_gateway') }}"
        @unless($isCurrent) style="display:none" @endunless>

        @foreach ($gateways as $key => $gw)
            @php
                $isSelected = ($isCurrent && $gateway === $key) || (count($gateways) === 1);
            @endphp

            <div class="lk-chip">
                <input type="radio" id="gateway__{{ $currCode }}_{{ $key }}"
                    name="gateway" value="{{ $key }}"
                    @checked($isSelected)
                    @disabled(!$isCurrent)
                    data-fee="{{ $gw['fee'] ?? 0 }}"
                    data-bonus="{{ $gw['bonus'] ?? 0 }}"
                    data-min="{{ $gw['minimum_amount'] ?? '' }}"
                    aria-label="{{ $gw['name'] }}" />

                <label for="gateway__{{ $currCode }}_{{ $key }}">
                    <span class="lk-chip__icon">
                        @if (!empty($gw['image']))
                            <img src="{{ asset($gw['image']) }}" alt="{{ $gw['name'] }}" loading="lazy" />
                        @else
                            <x-icon path="ph.regular.credit-card" />
                        @endif
                    </span>
                    <span class="lk-chip__name">{{ $gw['name'] }}</span>
                    @if (($gw['bonus'] ?? 0) > 0)
                        <span class="lk-chip__bonus">+{{ $gw['bonus'] }}%</span>
                    @endif
                    @if (($gw['fee'] ?? 0) > 0)
                        <span class="lk-chip__fee">{{ $gw['fee'] }}%</span>
                    @endif
                </label>
            </div>
        @endforeach
    </div>

    @if (count($gateways) === 1)
        <input type="hidden" data-lk-gateway-hidden="{{ $currCode }}"
            name="{{ $isCurrent ? 'gateway' : '' }}" value="{{ array_key_first($gateways) }}"
            @unless($isCurrent) disabled @endunless />
    @endif
@endforeach

@if (!$hasGateways)
    <div class="lk-methods-empty">
        {{ __('lk.no_gateways_for_currency') }}
    </div>
@endif
