<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\Server;
use Flute\Core\Http\Middlewares\CSRFMiddleware;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\JsonResponse;

class ServersController extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.servers');
        $this->middleware(CSRFMiddleware::class);
    }

    public function add(FluteRequest $request)
    {
        if (($check = $this->checkUnique($request->serverIp, $request->serverPort)) instanceof JsonResponse) {
            return $check;
        }

        $server = new Server;
        $server->name = $request->serverName;
        $server->ip = $request->serverIp;
        $server->port = $request->serverPort;
        $server->mod = $request->gameSelect;
        $server->display_ip = $request->displayIp;
        $server->rcon = $request->serverRcon;
        $server->enabled = filter_var($request->enabled, FILTER_VALIDATE_BOOL) ?? true;

        user()->log('events.server_added', $request->serverName . ' ' . $request->serverIp . ':' . $request->serverPort);

        transaction($server)->run();

        return $this->success();
    }

    public function delete(FluteRequest $request, string $id)
    {
        $server = $this->getServer((int) $id);

        if (!$server)
            return $this->error(__('admin.servers.not_found'), 404);

        user()->log('events.server_deleted', $id);

        transaction($server, 'delete')->run();

        return $this->success();
    }

    public function edit(FluteRequest $request, string $id)
    {
        $server = $this->getServer((int) $id);

        if (!$server)
            return $this->error(__('admin.servers.not_found'), 404);

        if (
            ($request->serverPort != $server->port || $request->serverIp != $server->ip) &&
            ($check = $this->checkUnique($request->serverIp, $request->serverPort)) instanceof JsonResponse
        ) {
            return $check;
        }

        $server->name = $request->serverName;
        $server->ip = $request->serverIp;
        $server->port = $request->serverPort;
        $server->mod = $request->gameSelect;
        $server->display_ip = $request->displayIp;
        $server->rcon = $request->serverRcon;
        $server->enabled = filter_var($request->enabled, FILTER_VALIDATE_BOOL) ?? true;

        user()->log('events.server_changed', $id);

        transaction($server)->run();

        return $this->success();
    }

    protected function getServer(int $id)
    {
        return rep(Server::class)->findByPK($id);
    }

    protected function checkUnique($ip, $port)
    {
        $server = rep(Server::class)->select()->where('ip', $ip)->where('port', $port);

        $server = $server->fetchAll();

        if ($server)
            return $this->error(__('admin.servers.server_duplicate'));

        return true;
    }
}