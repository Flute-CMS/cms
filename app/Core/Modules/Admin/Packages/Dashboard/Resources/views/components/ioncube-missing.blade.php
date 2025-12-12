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

<style>
.ioncube-missing-notice {
    font-size: 12px;
    line-height: 1.5;
}
.ioncube-missing-notice__header {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.ioncube-missing-notice__meta {
    font-size: 11px;
    opacity: 0.7;
    font-weight: 400;
}
.ioncube-missing-notice__desc {
    margin-top: 4px;
    opacity: 0.85;
}
.ioncube-missing-notice__error {
    margin-top: 8px;
    padding: 6px 10px;
    background: rgba(239, 68, 68, 0.15);
    border-radius: 6px;
    color: #f87171;
    font-size: 11px;
}
.ioncube-missing-notice__success {
    margin-top: 8px;
    padding: 6px 10px;
    background: rgba(34, 197, 94, 0.15);
    border-radius: 6px;
    color: #4ade80;
    font-size: 11px;
}
.ioncube-missing-notice__success code {
    display: block;
    margin-top: 4px;
    font-size: 10px;
    opacity: 0.8;
}
.ioncube-missing-notice__details {
    margin-top: 12px;
}
.ioncube-missing-notice__details summary {
    cursor: pointer;
    font-weight: 600;
    font-size: 11px;
    opacity: 0.9;
    user-select: none;
}
.ioncube-missing-notice__details summary:hover {
    opacity: 1;
}
.ioncube-missing-notice__steps {
    margin-top: 10px;
    padding-left: 4px;
}
.ioncube-missing-notice__steps > a {
    display: inline-block;
    margin-bottom: 10px;
    font-size: 11px;
    color: var(--accent-color, #f59e0b);
}
.step {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
}
.step__num {
    flex-shrink: 0;
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    font-size: 10px;
    font-weight: 600;
}
.step__content {
    flex: 1;
    min-width: 0;
}
.step__title {
    font-weight: 500;
    font-size: 11px;
}
.step__hint {
    font-size: 10px;
    opacity: 0.7;
    margin-top: 2px;
}
.step pre {
    margin: 4px 0 0;
    padding: 6px 8px;
    background: rgba(0,0,0,0.25);
    border-radius: 4px;
    font-size: 10px;
    overflow-x: auto;
    white-space: pre-wrap;
    word-break: break-all;
}
.ioncube-missing-notice__actions {
    margin-top: 12px;
}
.ioncube-missing-notice__actions .btn {
    font-size: 11px;
    gap: 6px;
}
</style>
