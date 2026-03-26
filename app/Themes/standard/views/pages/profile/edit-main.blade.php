@extends('flute::layouts.app')

@section('title')
    {{ __('def.settings') }}
@endsection

@push('scripts')
    @at(tt('assets/scripts/pages/profile-edit.js'))
@endpush

@push('content')
    <div class="container">
        <div class="profile-edit__layout">
            <aside class="profile-edit__sidebar">
                <nav class="profile-edit__sidebar-nav">
                    <p class="profile-edit__sidebar-label">{{ __('def.settings') }}</p>
                    <ul>
                        @foreach ($tabs as $key => $tab)
                            <li>
                                <a hx-get="{{ url('profile/settings')->addParams(['tab' => $tab['path']]) }}"
                                    class="profile-edit__sidebar-item @if ($tab['path'] === $activePath) active @endif"
                                    hx-target="#tab-content" hx-push-url="true" data-tab-path="{{ $tab['path'] }}" hx-swap="innerHTML show:#main:top">
                                    @if ($tab['icon'])
                                        <x-icon path="{{ $tab['icon'] }}" />
                                    @endif
                                    {!! __($tab['title']) !!}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
                <div class="profile-edit__sidebar-footer">
                    <a href="{{ url('profile/settings') }}" class="profile-edit__sidebar-item" hx-boost="true"
                        hx-target="#main" hx-swap="outerHTML transition:true">
                        <x-icon path="ph.regular.arrow-left" />
                        {{ __('def.back') }}
                    </a>
                </div>
            </aside>
            <div class="profile-edit__content">
                @fragment('profile-edit-card')
                    @if ($activeTab)
                        @php
                            $firstTab = $activeTab->first();
                            $isFullWidth = $firstTab->isFullWidth();
                        @endphp

                        <div class="profile-edit__card @if ($isFullWidth) profile-edit__card--full-width @endif" id="tab-content">
                            @if (!$isFullWidth)
                                <div class="profile-edit__card-header">
                                    <h3>{{ $firstTab->getTitle() }}</h3>

                                    @if ($desc = $firstTab->getDescription())
                                        <p>{{ $desc }}</p>
                                    @endif
                                </div>
                            @endif

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
