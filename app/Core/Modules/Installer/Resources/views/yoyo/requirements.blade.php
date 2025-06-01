<div class="installer__header">
    <h1>{{ __('install.requirements.title') }}</h1>
    <p>{{ __('install.requirements.description') }}</p>
</div>

<div class="requirements-step">
    <div class="installer-content-container">
        <div class="tabs-minimal">
            <div class="tabs-minimal__nav">
                <button class="tab-minimal active" data-tab="php">
                    PHP
                </button>
                <button class="tab-minimal" data-tab="extensions">
                    {{ __('install.requirements.extensions') }}
                </button>
                <button class="tab-minimal" data-tab="directories">
                    {{ __('install.requirements.directories') }}
                </button>
            </div>

            <div class="tab-minimal-content active" data-tab-content="php">
                @foreach($phpRequirements as $requirement)
                    <div class="requirement-item @if($requirement['status']) is-success @else is-error @endif">
                        <x-icon path="ph.regular.{{ $requirement['status'] ? 'check' : 'x' }}"
                            class="requirement-item__icon" />
                        <div class="requirement-item__info">
                            <div class="requirement-item__name">{{ $requirement['name'] }}</div>
                            <div class="requirement-item__version">{{ $requirement['current'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="tab-minimal-content" data-tab-content="extensions">
                <div class="requirement-compact-grid">
                    @foreach($extensionRequirements as $requirement)
                        <div class="requirement-compact @if($requirement['status']) is-success @else is-error @endif"
                            title="{{ $requirement['name'] }}">
                            <x-icon path="ph.regular.{{ $requirement['status'] ? 'check' : 'x' }}"
                                class="requirement-compact__icon" />
                            <span
                                class="requirement-compact__name">{{ str_replace(['Extension: ', 'Расширение: '], '', $requirement['name']) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="tab-minimal-content" data-tab-content="directories">
                <div class="directory-list">
                    @foreach($directoryRequirements as $requirement)
                        <div class="directory-item @if($requirement['status']) is-success @else is-error @endif">
                            <div class="directory-item__header">
                                <x-icon path="ph.regular.{{ $requirement['status'] ? 'check' : 'x' }}"
                                    class="directory-item__icon" />
                                <span
                                    class="directory-item__name">{{ str_replace(['Directory: ', 'Директория: '], '', $requirement['name']) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @if(! $allRequirementsMet)
            <div class="alert alert--danger">
                {{ __('install.requirements.fix_errors') }}
            </div>
        @endif
    </div>

    <div class="installer-form__actions">
        <x-button class="w-full" hx-get="{{ route('installer.step', ['id' => 1]) }}" hx-target="main" hx-push-url="true"
            hx-trigger="click" variant="secondary" yoyo:ignore>
            <x-icon path="ph.regular.arrow-left" />
            {{ __('install.common.back') }}
        </x-button>

        <x-button class="w-full" hx-get="{{ route('installer.step', ['id' => 3]) }}" hx-target="main" hx-push-url="true"
            hx-trigger="click" variant="primary" yoyo:ignore :disabled="!$allRequirementsMet">
            {{ __('install.common.next') }}
            <x-icon path="ph.regular.arrow-up-right" />
        </x-button>
    </div>
</div>