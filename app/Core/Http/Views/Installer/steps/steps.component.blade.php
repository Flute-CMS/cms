<div class="progress_block animation">
    <div class="progress-steps">
        <a href="{{ url('install/1') }}" class="step completed" data-tooltip="{{ __('install.1.card_head') }}"
            data-placement="left">
            @if ($all <= 1 || $current === 1)
                1
            @else
                <i class="ph-light ph-check animate__bounce"></i>
            @endif
        </a>
        <div class="step-line">
            <div class="progress full"></div>
        </div>
        <a href="{{ url('install/2') }}" class="step @if ($all > 2 || $current === 2) completed @endif"
            data-tooltip="{{ __('install.2.card_head') }}" data-placement="left">
            @if ($all <= 2 || $current === 2)
                2
            @else
                <i class="ph-light ph-check animate__bounce"></i>
            @endif
        </a>
        <div class="step-line">
            <div class="progress @if ($all >= 3 || $current === 3) full @endif"></div>
        </div>
        <a href="{{ url('install/3') }}" class="step @if ($all > 3 || $current === 3) completed @endif"
            data-tooltip="{{ __('install.3.card_head') }}" data-placement="left">
            @if ($all <= 3 || $current === 3)
                3
            @else
                <i class="ph-light ph-check animate__bounce"></i>
            @endif
        </a>
        <div class="step-line">
            <div class="progress @if ($all >= 4 || $current === 4) full @endif"></div>
        </div>
        <a href="{{ url('install/4') }}" class="step @if ($all > 4 || $current === 4) completed @endif"
            data-tooltip="{{ __('install.4.card_head') }}" data-placement="left">
            @if ($all <= 4 || $current === 4)
                4
            @else
                <i class="ph-light ph-check animate__bounce"></i>
            @endif
        </a>
        <div class="step-line">
            <div class="progress @if ($all >= 5 || $current === 5) full @endif"></div>
        </div>
        <a href="{{ url('install/5') }}" class="step @if ($all > 5 || $current === 5) completed @endif"
            data-tooltip="{{ __('install.5.card_head') }}" data-placement="left">
            @if ($all <= 5 || $current === 5)
                5
            @else
                <i class="ph-light ph-check animate__bounce"></i>
            @endif
        </a>
    </div>
</div>
