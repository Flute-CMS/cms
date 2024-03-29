<div class="position-relative row form-group">
    <div class="col-sm-4 col-form-label">
        <label>
            @t('admin.cache.all')
        </label>
        <small class="form-text text-muted">@t('admin.cache.all_desc')</small>
    </div>
    <div class="col-sm-8">
        <a class="btn primary size-s" href="{{ url('admin/api/cache/all') }}">@t('admin.cache.clear_all')</a>
    </div>
</div>
<div class="position-relative row form-group">
    <div class="col-sm-4 col-form-label">
        <label>
            @t('admin.cache.translate')
        </label>
        <small class="form-text text-muted">@t('admin.cache.translate_desc')</small>
    </div>
    <div class="col-sm-8">
        <a class="btn primary size-s" href="{{ url('admin/api/cache/all') }}">@t('admin.cache.clear_translate')</a>

    </div>
</div>
<div class="position-relative row form-group">
    <div class="col-sm-4 col-form-label">
        <label>
            @t('admin.cache.template')
        </label>
        <small class="form-text text-muted">@t('admin.cache.template_desc')</small>
    </div>
    <div class="col-sm-8">
        <a class="btn primary size-s" href="{{ url('admin/api/cache/all') }}">@t('admin.cache.clear_template')</a>
    </div>
</div>
<div class="position-relative row form-group">
    <div class="col-sm-4 col-form-label">
        <label>
            @t('admin.cache.styles')
        </label>
        <small class="form-text text-muted">@t('admin.cache.styles_desc')</small>
    </div>
    <div class="col-sm-8">
        <a class="btn primary size-s" href="{{ url('admin/api/cache/all') }}">@t('admin.cache.clear_styles')</a>
    </div>
</div>
