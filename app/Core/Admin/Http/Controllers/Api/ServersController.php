<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\Server;
use Flute\Core\Exceptions\RequestValidateException;
use Flute\Core\Http\Middlewares\CSRFMiddleware;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use xPaw\SourceQuery\SourceQuery;

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

    public function checkIp(FluteRequest $request)
    {
        try {
            $request->validate([
                'ip' => 'string',
                'port' => 'numeric',
                'game' => 'numeric'
            ]);
        } catch (RequestValidateException $e) {
            return $this->error($e->getErrors());
        }

        $ip = $request->input('ip');
        $port = $request->input('port');
        $game = $request->input('game');

        try {
            $query = new SourceQuery();
            $query->Connect($ip, $port, 5, ((int) $game === 10) ? SourceQuery::GOLDSOURCE : SourceQuery::SOURCE);

            return $this->success("Успешное подключение. Игроков - " . $query->GetInfo()['Players']);
        } catch (\Exception $e) {
            return $this->error("No connection to the server");
        } finally {
            if ($query !== null) {
                $query->Disconnect();
            }
        }
    }

    public function checkRcon(FluteRequest $request)
    {
        try {
            $request->validate([
                'ip' => 'string',
                'port' => 'numeric',
                'game' => 'numeric',
                'rcon' => 'string',
                'command' => 'string'
            ]);
        } catch (RequestValidateException $e) {
            return $this->error($e->getErrors());
        }

        $ip = $request->input('ip');
        $port = $request->input('port');
        $game = $request->input('game');
        $rcon = $request->input('rcon');
        $command = $request->input('command');

        try {
            $query = new SourceQuery();

            $query->Connect($ip, $port, 3, ((int) $game === 10) ? SourceQuery::GOLDSOURCE : SourceQuery::SOURCE);
            $query->SetRconPassword($rcon);

            $rcon = $query->Rcon($command);

            return $this->json([
                'result' => $rcon
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        } finally {
            if ($query !== null) {
                $query->Disconnect();
            }
        }
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