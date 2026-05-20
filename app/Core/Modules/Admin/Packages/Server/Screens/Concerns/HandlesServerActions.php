<?php

namespace Flute\Admin\Packages\Server\Screens\Concerns;

use Flute\Core\ServerQuery\ServerQueryService;
use Illuminate\Support\Str;
use Throwable;

trait HandlesServerActions
{
    public function saveServer()
    {
        $data = request()->input();

        foreach (['mod', 'ranks', 'ranks_format'] as $selectField) {
            if (isset($data[$selectField]) && is_array($data[$selectField])) {
                $data[$selectField] = $data[$selectField][0] ?? null;
            }
        }

        if (request()->input('tab-server-edit') === Str::slug(__('admin-server.tabs.db_connections'))) {
            $this->flashMessage(__('admin-server.messages.save_not_for_db_connections'), 'error');

            return;
        }

        $validation = $this->validate([
            'name' => ['required', 'string', 'max-str-len:255'],
            'ip' => ['required', 'string', 'max-str-len:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'mod' => ['required', 'string', 'max-str-len:255'],
            'rcon' => ['nullable', 'string', 'max-str-len:255'],
            'display_ip' => ['nullable', 'string', 'max-str-len:255'],
            'enabled' => ['required', 'boolean'],
            'ranks' => ['nullable', 'string', 'max-str-len:255'],
            'ranks_format' => ['nullable', 'string', 'max-str-len:255'],
            'ranks_premier' => ['nullable', 'boolean'],
            'settings__query_port' => ['nullable', 'max:65535'],
            'settings__rcon_port' => ['nullable', 'max:65535'],
        ], $data);

        if (!$validation) {
            return;
        }

        if (str_contains((string) ( $data['ip'] ?? '' ), ':')) {
            $this->inputError('ip', __('admin-server.messages.invalid_ip'));

            return;
        }

        try {
            $server = $this->serversService->saveServer($this->server, $data);

            if (!$this->serverId) {
                $this->flashMessage(__('admin-server.messages.server_created'), 'success');
                $this->redirect('/admin/servers/' . $server->id . '/edit');
            } else {
                $this->flashMessage(__('admin-server.messages.server_updated'), 'success');
            }
        } catch (Throwable $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    public function uploadRankPack()
    {
        $file = request()->files->get('ranks_archive');

        if (!$file || !$file->isValid()) {
            $this->flashMessage(__('admin-server.ranks_upload.no_file'), 'error');

            return;
        }

        try {
            $packName = $this->serversService->uploadRankPack($file);
            $this->flashMessage(__('admin-server.ranks_upload.success', ['name' => $packName]), 'success');
        } catch (Throwable $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    public function deleteServer()
    {
        if (!user()->can('admin.servers')) {
            $this->flashMessage(__('admin-server.messages.no_permission.delete'), 'error');

            return;
        }

        try {
            $this->server->delete();
            $this->flashMessage(__('admin-server.messages.delete_success'), 'success');
            $this->redirectTo('/admin/servers');
        } catch (Throwable $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    public function testConnection()
    {
        if (!$this->server) {
            $this->flashMessage(__('admin-server.messages.save_server_first'), 'error');

            return;
        }

        try {
            $queryService = app(ServerQueryService::class);
            $result = $queryService->query($this->server);

            if ($result->online) {
                $vac = isset($result->additional['vac'])
                    ? ( $result->additional['vac'] ? __('def.yes') : __('def.no') )
                    : null;

                $totalPlayers = count($result->playersData);
                $playerNames = array_slice(array_column($result->playersData, 'name'), 0, 5);

                $this->serverStatus = [
                    'online' => true,
                    'hostname' => $result->hostname ?? __('def.unknown'),
                    'map' => $result->map ?? __('def.unknown'),
                    'players' => $result->players . '/' . $result->maxPlayers,
                    'game' => $result->additional['folder'] ?? $this->serversService->getGameName($this->server->mod),
                    'vac' => $vac,
                    'player_list' => $playerNames,
                    'player_list_truncated' => $totalPlayers > 5,
                    'players_total' => $totalPlayers > 5 ? $totalPlayers - 5 : 0,
                ];

                $this->flashMessage(__('admin-server.messages.connection_success'), 'success');
            } else {
                $this->serverStatus = [
                    'online' => false,
                    'error' => __('admin-server.messages.connection_no_response'),
                ];

                $this->flashMessage(__('admin-server.messages.connection_no_response'), 'warning');
            }
        } catch (Throwable $e) {
            $this->serverStatus = [
                'online' => false,
                'error' => $e->getMessage(),
            ];

            $this->flashMessage(__('admin-server.messages.connection_failed') . ': ' . $e->getMessage(), 'error');
        }
    }
}
