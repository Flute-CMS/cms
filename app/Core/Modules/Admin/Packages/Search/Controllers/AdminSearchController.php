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
        $query = $request->input('query', '');

        $results = app(AdminSearchHandler::class)->emit($query);

        if ($request->isOnlyHtmx()) {
            return view('admin-search::results', ['results' => $results, 'query' => $query]);
        }

        return response()->json($results);
    }

    public function slashCommands(FluteRequest $request)
    {
        $query = $request->input('query', '/');

        if (strpos($query, ' ') !== false) {
            $results = app(AdminSearchHandler::class)->emit($query);
            if ($request->isOnlyHtmx()) {
                return view('admin-search::results', ['results' => $results, 'query' => $query]);
            }

            return response()->json($results);
        }

        $prefix = '';

        if (strlen($query) > 1 && $query[0] == '/') {
            $prefix = substr($query, 1);
        }

        $allCommands = SlashCommandsRegistry::all();
        $filteredCommands = [];

        if (empty($allCommands)) {
            SlashCommandsRegistry::register('user', __('search.search_users'), 'ph.regular.user');
            SlashCommandsRegistry::register('settings', __('search.settings'), 'ph.regular.gear');
            $allCommands = SlashCommandsRegistry::all();
        }

        if (!empty($prefix)) {
            foreach ($allCommands as $command) {
                $cmdName = substr($command['command'], 1);
                if (stripos($cmdName, $prefix) === 0) {
                    $filteredCommands[] = $command;
                }
            }
        } else {
            $filteredCommands = $allCommands;
        }

        if ($request->isOnlyHtmx()) {
            if (!empty($prefix) && empty($filteredCommands)) {
                $results = app(AdminSearchHandler::class)->emit($query);

                return view('admin-search::results', ['results' => $results, 'query' => $query]);
            }

            return view('admin-search::commands', ['commands' => $filteredCommands]);
        }

        return response()->json($filteredCommands);
    }
}
