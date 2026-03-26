<div class="rank-upload" data-rank-dropzone>
    <input type="file" name="ranks_archive" accept=".zip" data-rank-file-input />

    <span class="rank-upload__icon" data-rank-icon>
        <x-icon path="ph.bold.file-zip-bold" />
    </span>

    <div class="rank-upload__info">
        <strong data-rank-dropzone-text>{{ __('admin-server.ranks_upload.dropzone') }}</strong>
        <span data-rank-dropzone-hint>{{ __('admin-server.ranks_upload.hint') }}</span>
    </div>

    <button type="button" class="btn primary size-s rank-upload__btn" data-rank-upload-btn disabled>
        <span data-rank-btn-label>{{ __('admin-server.ranks_upload.button') }}</span>
    </button>
</div>
