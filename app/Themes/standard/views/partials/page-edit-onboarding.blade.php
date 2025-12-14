<div class="page-edit-onboarding" id="pageEditOnboarding" style="display: none;">
    <div class="page-edit-onboarding-inner">
        <div class="page-edit-onboarding-slides" id="onboardingSlides">
            @php
                $onboardingSlides = [
                    [
                        'type' => 'image',
                        'media' => url('assets/img/onboarding/slide1.png'),
                        'title' => __('page.onboarding.colors.title'),
                        'description' => __('page.onboarding.colors.description'),
                    ],
                    [
                        'type' => 'image',
                        'media' => url('assets/img/onboarding/slide2.png'),
                        'title' => __('page.onboarding.widgets.title'),
                        'description' => __('page.onboarding.widgets.description'),
                    ],
                    [
                        'type' => 'image',
                        'media' => url('assets/img/onboarding/slide3.png'),
                        'title' => __('page.onboarding.widgets.settings.title'),
                        'description' => __('page.onboarding.widgets.settings.description'),
                    ],
                    [
                        'type' => 'gif',
                        'media' => url('assets/img/onboarding/slide4.gif'),
                        'title' => __('page.onboarding.try.title'),
                        'description' => __('page.onboarding.try.description'),
                    ],
                ];
            @endphp

            @foreach ($onboardingSlides as $slide)
                <div class="page-edit-onboarding-slide" data-slide-index="{{ $loop->index }}">
                    <div class="slide-media">
                        @if ($slide['type'] === 'video')
                            <video src="{{ $slide['media'] }}" autoplay muted loop></video>
                        @elseif($slide['type'] === 'gif' || $slide['type'] === 'image')
                            <img src="{{ $slide['media'] }}" alt="{{ $slide['title'] }}" />
                        @endif
                    </div>
                    <div class="slide-content">
                        <h3>{{ $slide['title'] }}</h3>
                        <p>{{ $slide['description'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="page-edit-onboarding-controls">
            <div class="page-edit-onboarding-indicators" id="onboardingIndicators"></div>
            <x-button type="primary" id="onboardingNextBtn" class="next-btn">{{ __('page.onboarding.next') }}</x-button>
        </div>
    </div>
</div>
