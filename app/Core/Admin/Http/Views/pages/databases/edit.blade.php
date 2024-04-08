@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.databases.edit_title')]),
])

@push('content')
    <div class="admin-header d-flex align-items-center">
        <a href="{{ url('admin/databases/list') }}" class="back_btn">
            <i class="ph ph-caret-left"></i>
        </a>
        <div>
            <h2>@t('admin.databases.edit_title')</h2>
            <p>@t('admin.databases.edit_description')</p>
        </div>
    </div>

    <form data-form="edit" data-page="databases" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="id" value="{{ $connection->id }}">
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="dbname">@t('admin.databases.dbname')</label>
                <small class="form-text text-muted">@t('admin.databases.dbname_desc')</small>
            </div>
            <div class="col-sm-9">
                <select name="dbname" id="dbname" class="form-control">
                    @foreach (config('database.databases') as $key => $val)
                        <option value="{{ $key }}" @if ($key === $connection->dbname) selected @endif>
                            {{ $key }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="mod">@t('admin.databases.mod')</label>
                <small class="form-text text-muted">@t('admin.databases.mod_desc')</small>
            </div>
            <div class="col-sm-9">
                <input type="text" id="mod" name="mod" value="{{ $connection->mod }}"
                    placeholder="Vip / IKS / etc." required>
            </div>
        </div>

        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="additional">
                    @t('admin.databases.settings')
                </label>
            </div>
            <div class="col-sm-9">
                <div id="editorAce">{{ $connection->additional }}</div>
            </div>
        </div>

        <!-- Кнопка отправки -->
        <div class="position-relative row form-check">
            <div class="col-sm-9 offset-sm-3">
                <button type="submit" data-save class="btn size-m btn--with-icon primary">
                    @t('def.save')
                    <span class="btn__icon arrow"><i class="ph ph-arrow-right"></i></span>
                </button>
            </div>
        </div>
    </form>
@endpush

@push('footer')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/js-beautify/1.15.1/beautify.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.12/ace.js" type="text/javascript" charset="utf-8"></script>
@endpush