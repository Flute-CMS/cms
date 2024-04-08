<form>
    @csrf

    <!-- Выбор базы данных по умолчанию -->
    <div class="position-relative row form-group" id="tip_def">
        <div class="col-sm-3 col-form-label required">
            <label for="defaultDatabase">@t('admin.form_database.default_database_label')</label>
            <small class="form-text text-muted">@t('admin.form_database.default_database_description')</small>
        </div>
        <div class="col-sm-9">
            <select name="defaultDatabase" id="defaultDatabase" class="form-control">
                @foreach (config('database.databases') as $key => $val)
                    <option value="{{ $key }}" @if (config('database.default') == $key) selected @endif>
                        {{ $key }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Режим отладки -->
    <div class="position-relative row form-group" id="tip_deb">
        <div class="col-sm-3 col-form-label">
            <label for="debugMode">@t('admin.form_database.debug_mode_label')</label>
            <small class="form-text text-muted">@t('admin.form_database.debug_mode_description')</small>
        </div>
        <div class="col-sm-9">
            <input name="debugMode" role="switch" id="debugMode" type="checkbox" class="form-check-input"
                {{ config('database.debug') ? 'checked' : '' }}>
            <label for="debugMode"></label>
        </div>
    </div>

    <!-- Управление подключениями -->
    <div class="position-relative row form-group align-items-start" id="tip_con">
        <div class="col-sm-3 col-form-label">
            <label>@t('admin.form_database.manage_connections_label')</label>
            <small class="form-text text-muted">@t('admin.form_database.manage_connections_description')</small>
        </div>

        <!-- Список текущих подключений -->
        <div class="col-sm-9">
            <div class="connection_items">
                @foreach (config('database.connections') as $db => $val)
                    <div class="connection-item">
                        <div class="connection-name" data-database="{{ $db }}">
                            {{ $db }}
                        </div>
                        <div class="connection_buttons">
                            <button type="button" class="btn-change" data-changeconnection="{{ $db }}"
                                data-values="{{ json_encode($val) }}"><i class="ph ph-pencil"></i></button>
                            <button type="button" class="btn-delete" data-deleteconnection="{{ $db }}"><i
                                    class="ph ph-trash"></i></button>
                        </div>
                    </div>
                @endforeach
            </div>

            <button type="button" data-addconnection class="btn size-s outline">@t('admin.form_database.add_new_connection_button')</button>
        </div>
    </div>

    <!-- Управление базами данных -->
    <div class="position-relative row form-group align-items-start" id="tip_dbs">
        <div class="col-sm-3 col-form-label">
            <label>@t('admin.form_database.manage_databases_label')</label>
            <small class="form-text text-muted">@t('admin.form_database.manage_databases_description')</small>
        </div>
        
        <div class="col-sm-9">
            <div class="connection_items">
                @foreach (config('database.databases') as $db => $val)
                    <div class="connection-item">
                        <div class="connection-name">
                            {{ $db }}
                        </div>
                        <div class="connection_buttons">
                            <button type="button" class="btn-change" data-changedb="{{ $db }}"
                                data-values="{{ json_encode($val) }}"><i class="ph ph-pencil"></i></button>
                            <button type="button" class="btn-delete" data-deletedb="{{ $db }}"><i
                                    class="ph ph-trash"></i></button>
                        </div>
                    </div>
                @endforeach
            </div>

            <button type="button" data-addb class="btn size-s outline">@t('admin.form_database.add_new_database_button')</button>
        </div>
    </div>

    <!-- Кнопка сохранения -->
    <div class="position-relative row form-check">
        <div class="col-sm-9 offset-sm-3">
            <button type="submit" data-save class="btn size-m btn--with-icon primary">
                @t('def.save')
                <span class="btn__icon arrow"><i class="ph ph-arrow-right"></i></span>
            </button>
        </div>
    </div>
</form>
