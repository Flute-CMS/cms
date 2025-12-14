<x-card class="top-donors" withoutPadding>
    <x-slot name="header">
        <div class="top-donors-header">
            <h5>
                <span class="top-donors-icon">
                    <x-icon path="ph.regular.money" />
                </span>
                {{ __('widgets.top_donors') }}
            </h5>
            <small class="text-muted top-donors-count">{{ count($users) }}</small>
        </div>
    </x-slot>
    <div class="top-donors-content">
        @if (!empty($users))
            <div class="top-donors-list" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">
                @foreach ($users as $index => $user)
                    <div class="top-donors-item">
                        <a href="{{ url('profile/' . $user['user']->getUrl()) }}" class="top-donors-user" data-user-card>
                            <div class="top-donors-rank top-donors-rank-{{ $index < 3 ? ($index + 1) : 'other' }}">{{ $index + 1 }}</div>
                            <div class="top-donors-avatar">
                                <img src="{{ asset($user['user']->avatar ?? config('profile.default_avatar')) }}" alt="{{ $user['user']->name }}" />
                            </div>
                            <span>{{ $user['user']->name }}</span>
                        </a>
                        <span class="top-donors-amount">{{ number_format($user['donated'], 2) }}
                            {{ config('lk.currency_view') }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="top-donors-empty">
                <x-icon path="ph.regular.money" />
                <p>{{ __('widgets.no_donors') }}</p>
            </div>
        @endif
    </div>
</x-card>
