<?php

namespace Flute\Admin\Http\Controllers;

use Flute\Admin\AdminPackageFactory;
use Flute\Core\Support\BaseController;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SidebarController extends BaseController
{
    public function getSidebar(): Response
    {
        cache()->delete('admin_menu_items');

        app(AdminPackageFactory::class)->clearMenuCache();

        return response()->view('admin::layouts.sidebar');
    }

    public function saveOrder(): JsonResponse
    {
        $body = json_decode(request()->getContent(), true);
        $order = $body['order'] ?? null;

        if (!is_array($order)) {
            return response()->json(['error' => 'Invalid order data'], 400);
        }

        $factory = app(AdminPackageFactory::class);
        $currentConfig = config('admin-menu') ?? $factory->getDefaultMenuConfig();

        $itemsByKey = [];
        $sectionsByTitle = [];

        foreach ($currentConfig as $entry) {
            if (isset($entry['section'])) {
                $sectionsByTitle[$entry['section']] = $entry;
            } elseif (isset($entry['key'])) {
                $itemsByKey[$entry['key']] = $entry;
            }
        }

        $newConfig = [];

        foreach ($order as $block) {
            $sectionKey = $block['section'] ?? null;
            $items = $block['items'] ?? [];

            if ($sectionKey !== null && $sectionKey !== '') {
                $newConfig[] = $sectionsByTitle[$sectionKey] ?? ['section' => $sectionKey];
            }

            foreach ($items as $itemKey) {
                if (isset($itemsByKey[$itemKey])) {
                    $newConfig[] = $itemsByKey[$itemKey];
                }
            }
        }

        $this->writeMenuConfig($newConfig);

        config()->set('admin-menu', $newConfig);
        $factory->clearMenuCache();
        cache()->delete('admin_menu_items');

        return response()->json(['success' => true]);
    }

    protected function writeMenuConfig(array $config): void
    {
        $configPath = config()->getConfigsPath();
        $filePath = $configPath . DIRECTORY_SEPARATOR . 'admin-menu.php';

        $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";

        if (file_put_contents($filePath, $content) === false) {
            throw new RuntimeException("Failed to write admin-menu config to {$filePath}");
        }

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($filePath, true);
        }
    }
}
