<div id="dropzone-overlay" class="dropzone-overlay">
    <div class="dropzone-overlay__content">
        <div class="upload-initial">
            <x-icon path="ph.bold.cloud-arrow-up-bold" class="dropzone-icon" />
            <h3>{{ __('admin-modules.dropzone.overlay_title') }}</h3>
            <p>{{ __('admin-modules.dropzone.overlay_description') }}</p>
        </div>

        <div class="upload-progress-view" style="display: none;">
            <div class="upload-progress-container">
                <div class="upload-progress-info">
                    <span id="upload-file-name"></span>
                    <span id="upload-progress-percent">0%</span>
                </div>
                <div class="upload-progress-bar">
                    <div class="upload-progress" id="upload-progress-bar" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>

    <input type="file" name="module_archive" id="module-file-input" style="display:none;" accept=".zip">
</div>