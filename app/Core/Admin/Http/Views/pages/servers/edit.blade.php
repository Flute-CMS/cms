@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.servers.edit_title', ['server' => $server->name])]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/servers.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <a class="back-btn" href="{{ url('admin/servers/list') }}">
                <i class="ph ph-arrow-left ignore"></i>
                @t('def.back')
            </a>
            <h2>@t('admin.servers.edit_title', ['server' => $server->name])</h2>
            <p>@t('admin.servers.edit_description')</p>
        </div>
        <div>
            <button data-deleteaction="{{ $server->id }}" data-deletepath="servers" class="btn size-s error outline">
                @t('def.delete')
            </button>
        </div>
    </div>

    <form id="editServer" data-sid="{{ $server->id }}">
        @csrf
        <div class="position-relative row form-group">
            <div class="col-sm-2 col-form-label required">
                <label for="serverName">
                    @t('admin.servers.name_label')
                </label>
            </div>
            <div class="col-sm-10">
                <input name="serverName" id="serverName" placeholder="@t('admin.servers.name_placeholder')" type="text" class="form-control"
                    value="{{ $server->name }}" required>
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
                    value="{{ $server->ip }}" required>
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
                <input name="serverPort" id="serverPort" value="{{ $server->port }}" placeholder="@t('admin.servers.port_placeholder')"
                    type="number" class="form-control" required>
            </div>
        </div>

        <!-- Display IP -->
        <div class="position-relative row form-group">
            <div class="col-sm-2 col-form-label required">
                <label for="displayIp">
                    @t('admin.servers.display_ip')
                </label>
            </div>
            <div class="col-sm-10">
                <input name="displayIp" id="displayIp" value="{{ $server->display_ip }}" placeholder="@t('admin.servers.display_ip')"
                    type="text" class="form-control" required>
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
                <input name="serverRcon" id="serverRcon" value="{{ $server->rcon }}" placeholder="@t('admin.servers.rcon_placeholder')"
                    type="password" class="form-control">
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
                <select name="gameSelect" id="gameSelect" class="form-control" value="{{ $server->mod }}">
                    <option value="730">CS 2 / CS:GO</option>
                    <option value="240">CS:S</option>
                    <option value="10">Counter-Strike 1.6</option>
                    <option value="440">Team Fortress 2</option>
                    <option value="550">Left 4 Dead 2</option>
                    <option value="1002">Rag Doll Kung Fu</option>
                    <option value="2400">The Ship</option>
                    <option value="4000">Garry's Mod</option>
                    <option value="17710">Nuclear Dawn</option>
                    <option value="70000">Dino D-Day</option>
                    <option value="107410">Arma 3</option>
                    <option value="115300">Call of Duty: Modern Warfare 3</option>
                    <option value="162107">DeadPoly</option>
                    <option value="211820">Starbound</option>
                    <option value="244850">Space Engineers</option>
                    <option value="304930">Unturned</option>
                    <option value="251570">7 Days to Die</option>
                    <option value="252490">Rust</option>
                    <option value="282440">Quake Live</option>
                    <option value="346110">ARK: Survival Evolved</option>
                    <option value="minecraft">Minecraft</option>
                    <option value="108600">Project: Zomboid</option>
                    <option value="all_hl_games_mods">HL1 / HL2 Game</option>
                </select>
            </div>
        </div>

        <div class="position-relative row form-group">
            <div class="col-sm-2 col-form-label">
                <label for="enabled">
                    @t('admin.servers.enabled')</label>
                <small>@t('admin.servers.enabled_description')</small>
            </div>
            <div class="col-sm-10">
                <input name="enabled" @if ($server->enabled) checked @endif role="switch" id="enabled"
                    type="checkbox" class="form-check-input">
                <label for="enabled"></label>
            </div>
        </div>

        <div class="position-relative row form-group">
            <div class="col-sm-2 col-form-label">
                <label>
                    @t('admin.servers.check')</label>
                <small>@t('admin.servers.check_description')</small>
            </div>
            <div class="col-sm-10">
                <div class="d-flex" style="gap: 5px">
                    <button id="check_ip" type="button" class="btn primary size-s">@t('admin.servers.check_online')</button>
                    <button id="openModal" type="button"
                        class="btn primary size-s">@t('admin.servers.check_rcon')</button>
                </div>
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
    @at('Core/Admin/Http/Views/assets/js/pages/servers/edit.js')
@endpush
