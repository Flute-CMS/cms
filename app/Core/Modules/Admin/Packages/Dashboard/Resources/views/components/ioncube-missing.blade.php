@php
    /** @var \Flute\Core\Services\IoncubeService $ioncube */
    $os = $ioncube->getOsFamily();
    $arch = $ioncube->getMachineArch();
    $phpVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
    $downloadsPage = $ioncube::DOWNLOADS_PAGE_URL;
@endphp

<x-alert type="warning" withClose="false">
    <div class="ioncube-missing-notice">
        <div class="ioncube-missing-notice__header">
            <strong>@lang('admin-dashboard.ioncube.missing_title')</strong>
            <span class="ioncube-missing-notice__meta">{{ $os }} · {{ $arch }} · PHP {{ $phpVersion }}</span>
        </div>
        <div class="ioncube-missing-notice__desc">@lang('admin-dashboard.ioncube.missing_desc')</div>

        @if (!empty($download_error))
            <div class="ioncube-missing-notice__error">{{ $download_error }}</div>
        @endif

        @if (!empty($download) && is_array($download))
            <div class="ioncube-missing-notice__success">
                @lang('admin-dashboard.ioncube.download_success')
                <code>{{ $download['extractedPath'] ?? $download['archivePath'] ?? '' }}</code>
            </div>
        @endif

        <details class="ioncube-missing-notice__details">
            <summary>@lang('admin-dashboard.ioncube.install_title')</summary>
            <div class="ioncube-missing-notice__steps">
                <a href="{{ $downloadsPage }}" target="_blank" rel="noreferrer">{{ $downloadsPage }}</a>

                <div class="step">
                    <span class="step__num">1</span>
                    <div class="step__content">
                        <div class="step__title">@lang('admin-dashboard.ioncube.step1_title')</div>
                        <pre>php --ini && php -i | grep extension_dir</pre>
                    </div>
                </div>

                <div class="step">
                    <span class="step__num">2</span>
                    <div class="step__content">
                        <div class="step__title">@lang('admin-dashboard.ioncube.step2_title')</div>
                        <div class="step__hint">@lang('admin-dashboard.ioncube.step2_hint')</div>
                    </div>
                </div>

                <div class="step">
                    <span class="step__num">3</span>
                    <div class="step__content">
                        <div class="step__title">@lang('admin-dashboard.ioncube.step3_title')</div>
                        <pre>zend_extension="/path/to/ioncube/ioncube_loader_{{ strtolower(substr($os, 0, 3)) }}_{{ $phpVersion }}.{{ $os === 'Windows' ? 'dll' : 'so' }}"</pre>
                    </div>
                </div>

                <div class="step">
                    <span class="step__num">4</span>
                    <div class="step__content">
                        <div class="step__title">@lang('admin-dashboard.ioncube.step4_title')</div>
                        <pre>sudo systemctl restart php{{ $phpVersion }}-fpm</pre>
                    </div>
                </div>

                <div class="step">
                    <span class="step__num">5</span>
                    <div class="step__content">
                        <div class="step__title">@lang('admin-dashboard.ioncube.step5_title')</div>
                        <pre>php -m | grep -i ioncube</pre>
                    </div>
                </div>
            </div>
        </details>

        <div class="ioncube-missing-notice__actions">
            <button class="btn size-sm" yoyo:post="downloadIoncubeLoaders" type="button">
                <x-icon path="ph.bold.download-simple-bold" />
                @lang('admin-dashboard.ioncube.download_button')
            </button>
        </div>
    </div>
</x-alert>
