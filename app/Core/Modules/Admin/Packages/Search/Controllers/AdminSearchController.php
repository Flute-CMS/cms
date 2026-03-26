<?php

namespace Flute\Admin\Packages\Search\Controllers;

use Flute\Admin\Packages\Search\Services\AdminSearchHandler;
use Flute\Admin\Packages\Search\Services\SlashCommandsRegistry;
use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;

class AdminSearchController extends BaseController
{
    public function search(FluteRequest $request)
    {
        $query = trim($request->input('query', ''));

        if (empty($query)) {
            return view('admin-search::empty');
        }

        $results = app(AdminSearchHandler::class)->emit($query);

        return view('admin-search::results', ['results' => $results, 'query' => $query]);
    }

    public function slashCommands(FluteRequest $request)
    {
        $query = trim($request->input('query', '/'));

        if (strpos($query, ' ') !== false) {
            return $this->search($request);
        }

        $prefix = '';

        if (strlen($query) > 1 && $query[0] === '/') {
            $prefix = substr($query, 1);
        }

        $allCommands = SlashCommandsRegistry::all();
        $filteredCommands = [];

        if (!empty($prefix)) {
            foreach ($allCommands as $command) {
                $cmdName = substr($command['command'], 1);
                if (stripos($cmdName, $prefix) === 0) {
                    $filteredCommands[] = $command;
                }
            }

            if (empty($filteredCommands)) {
                return $this->search($request);
            }
        } else {
            $filteredCommands = $allCommands;
        }

        return view('admin-search::commands', ['commands' => $filteredCommands]);
    }
}
