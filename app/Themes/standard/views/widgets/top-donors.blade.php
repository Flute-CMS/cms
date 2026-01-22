@php
    $mode = $settings['display_mode'] ?? 'podium';
    $showAmount = $settings['show_amount'] ?? true;
@endphp

<x-card class="top-donors top-donors--{{ $mode }}">
    <x-slot name="header">
        <div class="top-donors__header">
            <h5>{{ __('widgets.top_donors') }}</h5>
        </div>
    </x-slot>

    @if (!empty($users))
        <div class="top-donors__content" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">

            {{-- Podium Mode --}}
            @if ($mode === 'podium')
                @php
                    $podium = array_slice($users, 0, 3);
                    $rest = array_slice($users, 3);
                    $podiumOrder = [];
                    if (isset($podium[1])) $podiumOrder[] = ['data' => $podium[1], 'place' => 2];
                    if (isset($podium[0])) $podiumOrder[] = ['data' => $podium[0], 'place' => 1];
                    if (isset($podium[2])) $podiumOrder[] = ['data' => $podium[2], 'place' => 3];
                @endphp

                @if (count($podium) > 0)
                    <div class="top-donors__podium">
                        @foreach ($podiumOrder as $item)
                            @php
                                $user = $item['data']['user'];
                                $donated = $item['data']['donated'];
                                $place = $item['place'];
                            @endphp
                            <a href="{{ url('profile/' . $user->getUrl()) }}"
                                data-user-card
                                class="top-donors__podium-item top-donors__podium-item--{{ $place }}">
                                <div class="top-donors__podium-avatar" data-place="{{ $place }}">
                                    <img src="{{ asset($user->avatar) }}" alt="{{ $user->name }}" loading="lazy">
                                    <span class="top-donors__podium-place">{{ $place }}</span>
                                </div>
                                <span class="top-donors__podium-name">{{ $user->name }}</span>
                                @if ($showAmount)
                                    <span class="top-donors__podium-amount">
                                        {{ number_format($donated, 0, '', ' ') }} {{ config('lk.currency_view') }}
                                    </span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endif

                @if (count($rest) > 0)
                    <div class="top-donors__list">
                        @foreach ($rest as $index => $item)
                            @php
                                $user = $item['user'];
                                $donated = $item['donated'];
                                $position = $index + 4;
                            @endphp
                            <a href="{{ url('profile/' . $user->getUrl()) }}"
                                data-user-card
                                class="top-donors__list-item">
                                <span class="top-donors__list-rank">{{ $position }}</span>
                                <img src="{{ asset($user->avatar) }}" alt="{{ $user->name }}"
                                    class="top-donors__list-avatar" loading="lazy">
                                <span class="top-donors__list-name">{{ $user->name }}</span>
                                @if ($showAmount)
                                    <span class="top-donors__list-amount">
                                        {{ number_format($donated, 0, '', ' ') }} {{ config('lk.currency_view') }}
                                    </span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endif

            {{-- List Mode --}}
            @elseif ($mode === 'list')
                <div class="top-donors__list top-donors__list--full">
                    @foreach ($users as $index => $item)
                        @php
                            $user = $item['user'];
                            $donated = $item['donated'];
                            $position = $index + 1;
                        @endphp
                        <a href="{{ url('profile/' . $user->getUrl()) }}"
                            data-user-card
                            class="top-donors__list-item {{ $position <= 3 ? 'top-donors__list-item--' . $position : '' }}">
                            <span class="top-donors__list-rank">{{ $position }}</span>
                            <img src="{{ asset($user->avatar) }}" alt="{{ $user->name }}"
                                class="top-donors__list-avatar" loading="lazy">
                            <span class="top-donors__list-name">{{ $user->name }}</span>
                            @if ($showAmount)
                                <span class="top-donors__list-amount">
                                    {{ number_format($donated, 0, '', ' ') }} {{ config('lk.currency_view') }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>

            {{-- Compact Mode --}}
            @elseif ($mode === 'compact')
                <div class="top-donors__compact">
                    @foreach ($users as $index => $item)
                        @php
                            $user = $item['user'];
                            $donated = $item['donated'];
                            $position = $index + 1;
                        @endphp
                        <a href="{{ url('profile/' . $user->getUrl()) }}"
                            data-user-card
                            class="top-donors__compact-item {{ $position <= 3 ? 'top-donors__compact-item--' . $position : '' }}"
                            data-tooltip="{{ $user->name }}{{ $showAmount ? ' — ' . number_format($donated, 0, '', ' ') . ' ' . config('lk.currency_view') : '' }}">
                            <img src="{{ asset($user->avatar) }}" alt="{{ $user->name }}" loading="lazy">
                            @if ($position <= 3)
                                <span class="top-donors__compact-badge">{{ $position }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    @else
        <div class="top-donors__empty">
            <x-icon path="ph.regular.heart" />
            <span>{{ __('widgets.no_donors') }}</span>
        </div>
    @endif
</x-card>
