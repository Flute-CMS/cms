<?php

namespace Flute\Core\Modules\Icons\Controllers;

use Flute\Core\Modules\Icons\Services\IconFinder;
use Flute\Core\Router\Annotations\Get;
use Flute\Core\Router\Annotations\Route;
use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Route('admin/api/icons', middleware: ['can:admin'])]
class IconController extends BaseController
{
    /**
     */
    protected IconFinder $iconFinder;

    /**
     * IconController constructor.
     */
    public function __construct(
        IconFinder $iconFinder,
    ) {
        $this->iconFinder = $iconFinder;
    }

    /**
     * get list of all available icon packages.
     *
     * @Get("packages")
     */
    #[Get('packages')]
    public function getPackages()
    {
        $packages = $this->iconFinder->getPackages();

        $result = [];
        foreach ($packages as $prefix) {
            $category = $this->getCategoryForPrefix($prefix);
            $categories = $this->iconFinder->getCategoriesInPackage($prefix);

            $result[] = [
                'prefix' => $prefix,
                'name' => $category,
                'category' => $category,
                'categories' => $categories,
            ];
        }

        usort($result, static fn($a, $b) => strcmp($a['category'], $b['category']));

        return $this->jsonCached($result, 86400);
    }

    /**
     * Get list of all icons in the specified package.
     *
     * @Get("packages/{prefix}")
     */
    #[Get('packages/{prefix}')]
    public function getIcons(string $prefix, FluteRequest $request)
    {
        $categoryName = $request->input('category');
        $icons = $this->iconFinder->getIconsInPackage($prefix, $categoryName);
        $category = $this->getCategoryForPrefix($prefix);
        $categories = $this->iconFinder->getCategoriesInPackage($prefix);

        $limit = 100;
        $total = count($icons);
        $icons = array_slice($icons, 0, $limit);

        return $this->json([
            'prefix' => $prefix,
            'name' => $category,
            'category' => $category,
            'categories' => $categories,
            'currentCategory' => $categoryName,
            'icons' => $icons,
            'total' => $total,
            'limited' => $total > $limit,
        ]);
    }

    /**
     * Get categories for a specific icon package
     *
     * @Get("packages/{prefix}/categories")
     */
    #[Get('packages/{prefix}/categories')]
    public function getCategories(string $prefix)
    {
        $categories = $this->iconFinder->getCategoriesInPackage($prefix);
        $packageCategory = $this->getCategoryForPrefix($prefix);

        return $this->json([
            'prefix' => $prefix,
            'name' => $packageCategory,
            'category' => $packageCategory,
            'categories' => $categories,
        ]);
    }

    /**
     * Get list of all packages with their icons.
     *
     * @Get("all")
     */
    #[Get('all')]
    public function getAllIcons()
    {
        $packages = $this->iconFinder->getPackages();

        $result = [];
        foreach ($packages as $prefix) {
            $icons = $this->iconFinder->getIconsInPackage($prefix);
            $category = $this->getCategoryForPrefix($prefix);
            $categories = $this->iconFinder->getCategoriesInPackage($prefix);

            $result[] = [
                'prefix' => $prefix,
                'name' => $category,
                'category' => $category,
                'categories' => $categories,
                'icons' => $icons,
            ];
        }

        return $this->json($result);
    }

    /**
     * Render icon by path.
     *
     * @Get("render")
     */
    #[Get('render')]
    public function renderIcon(FluteRequest $request)
    {
        $path = $request->input('path');

        if (!$path) {
            return $this->json(['error' => 'Path parameter is required'], 400);
        }

        $svg = $this->iconFinder->loadFile($path);
        if (!$svg) {
            return $this->json(['error' => 'Icon not found'], 404);
        }

        return new Response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }

    /**
     * Search for icons by query string
     *
     * @Get("search")
     */
    #[Get('search')]
    public function searchIcons(FluteRequest $request)
    {
        $query = $request->input('q');
        $prefix = $request->input('prefix');
        $category = $request->input('category');

        if (empty($query) || strlen($query) < 2) {
            return $this->json(['error' => 'Search query is too short'], 400);
        }

        if (empty($prefix)) {
            return $this->json(['error' => 'Prefix parameter is required'], 400);
        }

        $cacheKey = 'icons.search.' . md5("{$prefix}.{$category}.{$query}");

        $result = cache()->callback($cacheKey, function () use ($prefix, $category, $query) {
            $allIcons = $this->iconFinder->getIconsInPackage($prefix, $category);
            $query = strtolower($query);

            $matchingIcons = [];
            foreach ($allIcons as $icon) {
                $iconName = strtolower(str_replace('-', ' ', $icon));
                if (str_contains($iconName, $query)) {
                    $matchingIcons[] = $icon;
                }
            }

            $paths = array_map(static fn($icon) => "{$prefix}.{$icon}", $matchingIcons);
            $paths = array_slice($paths, 0, 300);

            $icons = [];
            foreach ($paths as $path) {
                $svg = $this->iconFinder->loadFile($path);
                if ($svg) {
                    $icons[] = [
                        'path' => $path,
                        'svg' => $svg,
                        'displayName' => $this->getDisplayNameFromPath($path),
                    ];
                }
            }

            return [
                'prefix' => $prefix,
                'query' => $query,
                'category' => $category,
                'total' => count($matchingIcons),
                'icons' => $icons,
            ];
        }, 3600);

        return $this->jsonCached($result);
    }

    /**
     * Batch render multiple icons.
     *
     * @Get("batch-render")
     */
    #[Get('batch-render')]
    public function batchRenderIcons(FluteRequest $request)
    {
        $paths = $request->input('paths', []);
        $prefix = $request->input('prefix');
        $category = $request->input('category');
        $limit = min((int) $request->input('limit', 150), 300);
        $page = max((int) $request->input('page', 1), 1);

        if (!$prefix && empty($paths)) {
            return $this->json([
                'error' => 'Paths parameter is required when prefix is not specified',
            ], 400);
        }

        if ($prefix) {
            $cacheKey = "icons.batch.{$prefix}.{$category}.{$limit}.{$page}";

            $result = cache()->callback($cacheKey, function () use ($prefix, $category, $limit, $page) {
                $icons = $this->iconFinder->getIconsInPackage($prefix, $category);
                $total = count($icons);
                $icons = array_slice($icons, ($page - 1) * $limit, $limit);
                $paths = array_map(static fn($icon) => "{$prefix}.{$icon}", $icons);

                $rendered = [];
                foreach ($paths as $path) {
                    $svg = $this->iconFinder->loadFile($path);
                    if ($svg) {
                        $rendered[] = [
                            'path' => $path,
                            'svg' => $svg,
                            'displayName' => $this->getDisplayNameFromPath($path),
                        ];
                    }
                }

                return [
                    'prefix' => $prefix,
                    'category' => $category,
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'totalPages' => (int) ceil($total / $limit),
                    'icons' => $rendered,
                ];
            }, 86400);

            return $this->jsonCached($result, 86400);
        }

        $paths = array_slice($paths, 0, 300);
        $result = ['icons' => []];

        foreach ($paths as $path) {
            $svg = $this->iconFinder->loadFile($path);
            if ($svg) {
                $result['icons'][] = [
                    'path' => $path,
                    'svg' => $svg,
                    'displayName' => $this->getDisplayNameFromPath($path),
                ];
            }
        }

        return $this->jsonCached($result);
    }

    /**
     * Определить категорию по префиксу.
     */
    protected function getCategoryForPrefix(string $prefix): string
    {
        return match (ltrim(strtolower($prefix), '@')) {
            'ph' => 'Phosphor Icons',
            'fa' => 'Font Awesome',
            'si' => 'Simple Icons',
            'lu' => 'Lucide',
            'tb' => 'Tabler Icons',
            default => ucfirst($prefix),
        };
    }

    /**
     * Получить отображаемое имя из пути иконки.
     */
    protected function getDisplayNameFromPath(string $path): string
    {
        $parts = explode('.', $path);
        $prefix = $parts[0] ?? '';

        if ($prefix === 'ph') {
            $style = $parts[1] ?? '';
            $name = $parts[2] ?? '';

            if ($style === 'bold' && str_ends_with($name, '-bold')) {
                $name = substr($name, 0, -5);
            }

            return ucfirst(str_replace('-', ' ', $name));
        }

        if ($prefix === 'fa' || $prefix === 'tb') {
            // fa.folder.icon / tb.outline.icon
            $name = $parts[2] ?? ($parts[1] ?? '');

            return ucfirst(str_replace('-', ' ', $name));
        }

        // si.brands.icon / lu.icon
        return ucfirst(str_replace('-', ' ', end($parts)));
    }

    /**
     * Return JSON response with browser cache headers.
     */
    protected function jsonCached(array $data, int $maxAge = 3600): JsonResponse
    {
        $response = new JsonResponse($data);
        $response->headers->set('Cache-Control', "public, max-age={$maxAge}");

        return $response;
    }
}
