@extends('flute::layouts.app')

@section('title')
    {{ __('def.settings') }}
@endsection

@push('scripts')
    @at(tt('assets/scripts/pages/profile-edit.js'))
@endpush

@push('content')
    <div class="container">
        <div class="row gy-4 gx-4">
            <div class="col-lg-3 col-md-5">
                <aside class="profile-edit__sidebar mt-2">
                    <div class="profile-edit__sidebar-hero">
                        <img src="{{ asset($user->avatar ?? config('profile.default_avatar')) }}" alt="{{ $user->name }}" loading="lazy" data-profile-avatar="{{ $user->avatar }}">

                        <h5 data-profile-name="{{ $user->name }}">{{ $user->name }}</h5>
                        @if ($user->email)
                            <p data-profile-email="{{ $user->email }}">{{ $user->email }}</p>
                        @endif
                    </div>
                    <div class="profile-edit__sidebar-items">
                        <ul>
                            @foreach ($tabs as $key => $tab)
                                <li>
                                    <a hx-get="{{ url('profile/settings')->addParams(['tab' => $tab['path']]) }}"
                                        class="profile-edit__sidebar-item @if ($tab['path'] === $activePath) active @endif"
                                        hx-target="#tab-content" hx-push-url="true" data-tab-path="{{ $tab['path'] }}" hx-swap="innerHTML show:#main:top"
                                        @if ($tab['path'] === $activePath) hx-trigger="click once" @endif>
                                        @if ($tab['icon'])
                                            <x-icon path="{{ $tab['icon'] }}" />
                                        @endif
                                        {!! __($tab['title']) !!}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        <div class="profile-edit__sidebar-footer">
                            <a href="{{ url('profile/settings') }}" class="profile-edit__sidebar-item" hx-boost="true"
                                hx-target="#main" hx-swap="outerHTML transition:true">
                                <x-icon path="ph.regular.arrow-left" />
                                {{ __('def.back') }}
                            </a>
                        </div>
                    </div>
                </aside>
            </div>
            <div class="col-lg-9 col-md-7">
                @fragment('profile-edit-card')
                    @if ($activeTab)
                        <div class="profile-edit__card mt-2" id="tab-content">
                            <div class="profile-edit__card-header">
                                @php
                                    $firstTab = $activeTab->first();
                                @endphp

                                <h3>{{ $firstTab->getTitle() }}</h3>

                                @if ($desc = $firstTab->getDescription())
                                    <p>{{ $desc }}</p>
                                @endif
                            </div>

                            <div class="profile-edit__card-content">
                                {!! $activeTabContent !!}
                            </div>
                        </div>
                    @endif
                @endfragment
            </div>
        </div>
    </div>
@endpush
