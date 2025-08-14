<x-alert type="info" withClose="false" class="mb-0">
    {!! __('admin-social.edit.discord') !!}
</x-alert>

<x-forms.field>
    <x-forms.label for="settings__id" required>
        {{ __('admin-social.fields.client_id.label') }}
    </x-forms.label>
    <x-fields.input name="settings__id" id="settings__id"
        value="{{ request()->input('settings__id', $social ? $social->getSettings()['id'] : '') }}" required
        placeholder="{{ __('admin-social.edit.discord_id_placeholder') }}" />
    </x-forms.field>

<x-forms.field>
    <x-forms.label for="settings__secret" required>{{ __('admin-social.fields.client_secret.label') }}:</x-forms.label>
    <x-fields.input name="settings__secret" id="settings__secret" type="password"
        value="{{ request()->input('settings__secret', $social ? $social->getSettings()['secret'] : '') }}"
        placeholder="{{ __('admin-social.edit.discord_secret_placeholder') }}" required />
</x-forms.field>

<x-forms.field>
    <x-forms.label for="settings__token">
        {{ __('admin-social.edit.discord_token') }}
    </x-forms.label>
    <x-fields.input name="settings__token" id="settings__token"
        value="{{ request()->input('settings__token', $social ? $social->getSettings()['token'] : '') }}"
        placeholder="Вставьте сюда токен бота" type="password" />
    <small class="text-muted">
        {!! __('admin-social.edit.discord_token_help') !!}
    </small>
</x-forms.field>

<x-forms.field>
    <x-forms.label for="settings__guild_id">
        {{ __('admin-social.edit.discord_guild_id') }}
    </x-forms.label>
    <x-fields.input name="settings__guild_id" id="settings__guild_id"
        value="{{ request()->input('settings__guild_id', $social ? ($social->getSettings()['guild_id'] ?? '') : '') }}"
        placeholder="{{ __('admin-social.edit.discord_guild_id_placeholder') }}" />
    <small class="text-muted">{!! __('admin-social.edit.discord_guild_id_help') !!}</small>
 </x-forms.field>

@php
    $existingMap = $social ? ($social->getSettings()['roles_map'] ?? []) : [];
    $roles = \Flute\Core\Database\Entities\Role::findAll();
@endphp

<x-forms.field>
    <x-forms.label>
        {{ __('admin-social.edit.discord_roles_map') }}
    </x-forms.label>
    <div class="table-responsive">
        <table class="table table-minimal table-compact">
            <thead>
                <tr>
                    <th style="width:50%">{{ __('admin-social.edit.discord_roles_map_table.flute_role') }}</th>
                    <th>{{ __('admin-social.edit.discord_roles_map_table.discord_role_id') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($roles as $role)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge" style="background-color: {{ $role->color ?? '#999' }}">#{{ $role->id }}</span>
                            <span>{{ $role->name }}</span>
                        </div>
                    </td>
                    <td>
                        <x-fields.input name="settings__roles_map[{{ $role->id }}]" id="rm_{{ $role->id }}" type="text"
                            value="{{ request()->input('settings__roles_map.' . $role->id, $existingMap[$role->id] ?? '') }}"
                            placeholder="{{ __('admin-social.edit.discord_roles_map_placeholder') }}" />
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <small class="text-muted">{!! __('admin-social.edit.discord_roles_map_help') !!}</small>
 </x-forms.field>
