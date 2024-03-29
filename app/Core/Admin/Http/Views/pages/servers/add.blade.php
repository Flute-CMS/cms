@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.servers.add')]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/servers.scss')
@endpush

@push('content')
    <div class="admin-header d-flex align-items-center">
        <a href="{{ url('admin/servers/list') }}" class="back_btn">
            <i class="ph ph-caret-left"></i>
        </a>
        <div>
            <h2>@t('admin.servers.add_title')</h2>
            <p>@t('admin.servers.add_description')</p>
        </div>
    </div>

    <form id="addServer">
        @csrf
        <div class="position-relative row form-group">
            <div class="col-sm-2 col-form-label required">
                <label for="serverName">
                    @t('admin.servers.name_label')
                </label>
            </div>
            <div class="col-sm-10">
                <input name="serverName" id="serverName" placeholder="@t('admin.servers.name_placeholder')" type="text" class="form-control"
                    required>
            </div>
        </div>

        <!-- IP сервера -->
        <div class="position-relative row form-group">
            <div class="col-sm-2 col-form-label required">
                <label for="serverIp">
                    @t('admin.servers.ip_label')
                </label>
            </div>
            <div class="col-sm-10">
                <input name="serverIp" id="serverIp" placeholder="@t('admin.servers.ip_placeholder')" type="text" class="form-control"
                    required>
            </div>
        </div>

        <!-- Порт сервера -->
        <div class="position-relative row form-group">
            <div class="col-sm-2 col-form-label required">
                <label for="serverPort">
                    @t('admin.servers.port_label')
                </label>
            </div>
            <div class="col-sm-10">
                <input name="serverPort" id="serverPort" placeholder="@t('admin.servers.port_placeholder')" type="text" class="form-control"
                    required>
            </div>
        </div>

        <!-- RCON (опционально) -->
        <div class="position-relative row form-group">
            <div class="col-sm-2 col-form-label">
                <label for="serverRcon">
                    @t('admin.servers.rcon_label')
                </label>
            </div>
            <div class="col-sm-10">
                <input name="serverRcon" id="serverRcon" placeholder="@t('admin.servers.rcon_placeholder')" type="password"
                    class="form-control">
            </div>
        </div>

        <!-- Выбор игры -->
        <div class="position-relative row form-group">
            <div class="col-sm-2 col-form-label required">
                <label for="gameSelect">
                    @t('admin.servers.game_label')
                </label>
            </div>
            <div class="col-sm-10">
                <select name="gameSelect" id="gameSelect" class="form-control">
                    <option value="730">CS 2 / CS:GO</option>
                    <option value="240">CS:S</option>
                    <option value="10">Counter-Strike 1.6</option>
                    <option value="440">Team Fortress 2</option>
                    <option value="550">Left 4 Dead 2</option>
                </select>
            </div>
        </div>

        <!-- Кнопка отправки -->
        <div class="position-relative row form-check">
            <div class="col-sm-10 offset-sm-2">
                <button type="submit" data-save class="btn size-m btn--with-icon primary">
                    @t('def.save')
                    <span class="btn__icon arrow"><i class="ph ph-arrow-right"></i></span>
                </button>
            </div>
        </div>
    </form>
@endpush

@push('footer')
    @at('Core/Admin/Http/Views/assets/js/pages/servers/add.js')
@endpush
