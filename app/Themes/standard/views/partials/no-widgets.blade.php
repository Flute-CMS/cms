@if (empty(page()->getBlocks()))
    @php
        $stackContent = trim($__env->yieldPushContent('content'));
        $isHomePage = request()->getBaseUrl() === '/' || empty(request()->getBaseUrl());
    @endphp

    @if (empty($stackContent))
        <div class="container mt-5">
            <div class="row justify-content-center align-items-center">
                <div class="col-md-6 col-lg-4">
                    <x-card class="page-no-widgets">
                        <div class="icon">
                            <x-icon path="ph.regular.smiley-melting" />
                        </div>
                        <h4 class="mb-1">{{ __('widgets.no_widgets') }}</h4>
                        <p class="description">
                            {{ __('widgets.no_widgets_description') }} @if ($isHomePage)
                                {{ __('widgets.no_widgets_description_home') }}
                            @endif
                        </p>
                        @if ($isHomePage)
                            <x-button onclick="toggleEditMode(true)"><x-icon
                                    path="ph.regular.magic-wand" />{{ __('def.edit_page') }}</x-button>
                        @endif
                    </x-card>
                </div>
            </div>
        </div>
    @endif
@endif
