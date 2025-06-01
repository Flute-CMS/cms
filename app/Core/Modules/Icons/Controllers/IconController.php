<?php

namespace Flute\Core\Modules\Icons\Controllers;

use Flute\Core\Modules\Icons\Services\IconFinder;
use Flute\Core\Router\Annotations\Get;
use Flute\Core\Router\Annotations\Route;
use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

#[Route("admin/api/icons", middleware: ['can:admin'])]
class IconController extends BaseController
{
    /**
     * @var IconFinder
     */
    protected IconFinder $iconFinder;

    /**
     * IconController constructor.
     *
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
    #[Get("packages")]
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
                'categories' => $categories
            ];
        }

        usort($result, function ($a, $b) {
            return strcmp($a['category'], $b['category']);
        });

        return $this->json($result);
    }

    /**
     * Get list of all icons in the specified package.
     *
     * @Get("packages/{prefix}")
     * @param string $prefix
     */
    #[Get("packages/{prefix}")]
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
            'limited' => $total > $limit
        ]);
    }

    /**
     * Get categories for a specific icon package
     * 
     * @Get("packages/{prefix}/categories")
     */
    #[Get("packages/{prefix}/categories")]
    public function getCategories(string $prefix)
    {
        $categories = $this->iconFinder->getCategoriesInPackage($prefix);
        $packageCategory = $this->getCategoryForPrefix($prefix);
        
        return $this->json([
            'prefix' => $prefix,
            'name' => $packageCategory,
            'category' => $packageCategory,
            'categories' => $categories
        ]);
    }

    /**
     * Get list of all packages with their icons.
     *
     * @Get("all")
     */
    #[Get("all")]
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
    #[Get("render")]
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
    #[Get("search")]
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
        
        $allIcons = $this->iconFinder->getIconsInPackage($prefix, $category);
        $query = strtolower($query);
        
        $matchingIcons = [];
        foreach ($allIcons as $icon) {
            $iconName = strtolower(str_replace('-', ' ', $icon));
            
            if (strpos($iconName, $query) !== false) {
                $matchingIcons[] = $icon;
            }
        }
        
        $result = [
            'prefix' => $prefix,
            'query' => $query,
            'category' => $category,
            'total' => count($matchingIcons),
            'icons' => []
        ];
        
        $paths = array_map(function ($icon) use ($prefix) {
            return "$prefix.$icon";
        }, $matchingIcons);
        
        $paths = array_slice($paths, 0, min(count($paths), 500));
        
        foreach ($paths as $path) {
            $svg = $this->iconFinder->loadFile($path);
            
            if ($svg) {
                $displayName = $this->getDisplayNameFromPath($path);
                
                $result['icons'][] = [
                    'path' => $path,
                    'svg' => $svg,
                    'displayName' => $displayName
                ];
            }
        }
        
        return $this->json($result);
    }

    /**
     * Batch render multiple icons.
     *
     * @Get("batch-render")
     */
    #[Get("batch-render")]
    public function batchRenderIcons(FluteRequest $request)
    {
        $paths = $request->input('paths', []);
        $prefix = $request->input('prefix');
        $category = $request->input('category');
        $limit = (int)$request->input('limit', 50);
        $page = (int)$request->input('page', 1);

        if ($prefix) {
            $icons = $this->iconFinder->getIconsInPackage($prefix, $category);

            $total = count($icons);
            $icons = array_slice($icons, ($page - 1) * $limit, $limit);

            $paths = array_map(function ($icon) use ($prefix) {
                return "$prefix.$icon";
            }, $icons);

            $result = [
                'prefix' => $prefix,
                'category' => $category,
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => ceil($total / $limit),
                'icons' => []
            ];
        } else {
            if (empty($paths)) {
                return $this->json([
                    'error' => 'Paths parameter is required when prefix is not specified'
                ], 400);
            }

            $result = [
                'icons' => []
            ];
        }

        $paths = array_slice($paths, 0, min(count($paths), 200));

        foreach ($paths as $path) {
            $svg = $this->iconFinder->loadFile($path);

            if ($svg) {
                $displayName = $this->getDisplayNameFromPath($path);

                $result['icons'][] = [
                    'path' => $path,
                    'svg' => $svg,
                    'displayName' => $displayName
                ];
            }
        }

        return $this->json($result);
    }

    /**
     * Определить категорию по префиксу.
     *
     * @param string $prefix
     * @return string
     */
    protected function getCategoryForPrefix(string $prefix): string
    {
        $normalized = ltrim(strtolower($prefix), '@');
        if (str_starts_with($normalized, 'ph')) {
            return 'Phosphor Icons';
        } elseif (str_starts_with($normalized, 'fa')) {
            return 'Font Awesome';
        }
        return 'Other';
    }

    /**
     * Получить отображаемое имя из пути иконки.
     *
     * @param string $path
     * @return string
     */
    protected function getDisplayNameFromPath(string $path): string
    {
        $parts = explode('.', $path);
        $prefix = $parts[0] ?? '';

        if (str_starts_with($prefix, 'ph')) {
            $style = $parts[1] ?? '';
            $name = $parts[2] ?? '';

            if ($style === 'bold' && str_ends_with($name, '-bold')) {
                $name = substr($name, 0, -5);
            }

            return ucfirst(str_replace('-', ' ', $name));
        } elseif (str_starts_with($prefix, 'fa')) {
            // fa.folder.icon
            $name = $parts[2] ?? '';
            return ucfirst(str_replace('-', ' ', $name));
        } else {
            return ucfirst(str_replace('-', ' ', end($parts)));
        }
    }
}
