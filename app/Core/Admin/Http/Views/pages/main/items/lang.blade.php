<form>
    @csrf

    <!-- Выбор языка -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label required">
            <label for="locale">@t('admin.form_lang.language_label')</label>
            <small class="form-text text-muted">@t('admin.form_lang.language_description')</small>
        </div>
        <div class="col-sm-9">
            <select name="locale" id="locale" class="form-control">
                @foreach (app('lang.available') as $lang)
                    <option value="{{ $lang }}" {{ config('lang.locale') == $lang ? 'selected' : '' }}>
                        {{ __('langs.' . $lang) }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="position-relative row form-group align-items-start">
        <div class="col-sm-3 col-form-label required">
            <label for="locale">@t('admin.form_lang.langs_available')</label>
            <small class="form-text text-muted">@t('admin.form_lang.langs_available_description')</small>
        </div>
        <div class="col-sm-9">
            <div class="checkboxes">
                @foreach (config('lang.all') as $lang)
                    <div class="form-checkbox">
                        <input class="form-check-input" name="available[{{ $lang }}]" type="checkbox"
                            value="{{ $lang }}" id="available[{{ $lang }}]"
                            {{ in_array($lang, config('lang.available')) ? 'checked' : '' }}>
                        <label class="form-check-label flex-row" for="available[{{ $lang }}]">
                            <img class="lang_img" src="{{ url('assets/img/langs/' . $lang . '.svg') }}" alt="">
                            {{ strtoupper($lang) }}
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Кеширование -->
    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label">
            <label for="cache">
                <div class="may_unstable" data-tooltip="@t('admin.may_have_errors')" data-tooltip-conf="right multiline">
                    <i class="ph ph-warning"></i>
                </div>
                @t('admin.form_lang.caching_label')
            </label>
            <small class="form-text text-muted">@t('admin.form_lang.caching_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="cache" role="switch" id="cache" type="checkbox" class="form-check-input"
                {{ config('lang.cache') ? 'checked' : '' }}>
            <label for="cache"></label>
        </div>
    </div>

    <div class="position-relative row form-check">
        <div class="col-sm-9 offset-sm-3">
            <button type="submit" data-save class="btn size-m btn--with-icon primary">
                @t('def.save')
                <span class="btn__icon arrow"><i class="ph ph-arrow-right"></i></span>
            </button>
        </div>
    </div>
</form>
