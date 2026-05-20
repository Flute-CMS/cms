<?php

namespace Flute\Admin\Packages\Server\Screens\Concerns;

use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Core\Rcon\RconService;
use Throwable;

trait HandlesRcon
{
    public function executeRcon(): void
    {
        $command = trim((string) request()->input('rcon_command', ''));

        if ($command === '') {
            $this->flashMessage(__('admin-server.rcon.empty_command'), 'warning');

            return;
        }

        if (!$this->server || empty($this->server->rcon)) {
            $this->flashMessage(__('admin-server.rcon.no_rcon'), 'error');

            return;
        }

        try {
            $rconService = app(RconService::class);
            $output = $rconService->execute($this->server, $command);

            $this->rconHistory[] = [
                'cmd' => $command,
                'out' => $output !== '' ? $output : __('admin-server.rcon.no_output'),
                'ok' => true,
            ];
        } catch (Throwable $e) {
            $this->rconHistory[] = [
                'cmd' => $command,
                'out' => $e->getMessage(),
                'ok' => false,
            ];

            $this->flashMessage(__('admin-server.rcon.error') . ': ' . $e->getMessage(), 'error');
        }

        $this->saveRconHistory();
    }

    public function clearRcon(): void
    {
        $this->rconHistory = [];
        $this->saveRconHistory();
    }

    private function saveRconHistory(): void
    {
        if (count($this->rconHistory) > 50) {
            $this->rconHistory = array_slice($this->rconHistory, -50);
        }

        session()->set("rcon_history_{$this->serverId}", $this->rconHistory);
    }

    private function rconTabLayout()
    {
        return LayoutFactory::view('admin-server::partials.rcon-console', [
            'history' => $this->rconHistory,
            'server' => $this->server,
        ]);
    }
}
