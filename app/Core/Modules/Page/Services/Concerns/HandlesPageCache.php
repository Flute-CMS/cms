<?php

namespace Flute\Core\Modules\Page\Services\Concerns;

trait HandlesPageCache
{
    private function canUseRenderedPageCache(): bool
    {
        return (
            !is_cli()
            && !is_admin_path()
            && $this->currentPage !== null
            && !$this->request->query->has('editMode')
            && !$this->userService->isLoggedIn()
        );
    }

    private function readRenderedPageCache(): ?string
    {
        $file = $this->getRenderedPageCacheFile();
        if (!is_file($file)) {
            return null;
        }

        $ttl = (int) ( config('page.rendered_cache_ttl') ?? self::RENDERED_PAGE_CACHE_TIME );
        if ($ttl <= 0 || ( time() - ( @filemtime($file) ?: 0 ) ) > $ttl) {
            return null;
        }

        $content = @file_get_contents($file);

        if ($content === false) {
            return null;
        }

        $metaFile = $file . '.meta.php';
        $meta = is_file($metaFile) ? @include $metaFile : [];
        if ((bool) ( is_array($meta) ? $meta['global_content_rendered'] ?? false : false )) {
            $this->globalContentRendered = true;
        } elseif (str_contains($content, '<!-- __FLUTE_GLOBAL_CONTENT__ -->')) {
            $this->globalContentRendered = true;
        }

        return $content;
    }

    private function writeRenderedPageCache(string $content): void
    {
        if (!$this->canUseRenderedPageCache() || $content === '') {
            return;
        }

        $file = $this->getRenderedPageCacheFile();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0o775, true);
        }

        @file_put_contents($file, $content, LOCK_EX);
        @file_put_contents(
            $file . '.meta.php',
            '<?php return '
            . var_export([
                'global_content_rendered' => $this->globalContentRendered,
            ], true)
            . ';',
            LOCK_EX,
        );
    }

    private function getRenderedPageCacheFile(): string
    {
        $device = $this->getRequestDeviceSegment();
        $pageId = $this->currentPage?->id ?? 'none';
        $route = $this->currentPage?->route ?? $this->request->getPathInfo();
        $key = sha1(implode('|', [
            app()->getLang(),
            $device,
            (string) $pageId,
            (string) $route,
        ]));

        return storage_path("app/cache/page-rendered/{$key}.html");
    }

    private function getRequestDeviceSegment(): string
    {
        $userAgent = $this->request->headers->get('User-Agent', '');
        $isMobile = (bool) preg_match('/Mobile|Android.*Mobile|iPhone|iPod/i', $userAgent);
        if ($isMobile) {
            return 'mobile';
        }

        $isTablet = (bool) preg_match('/iPad|Android|Tablet/i', $userAgent);

        return $isTablet ? 'tablet' : 'desktop';
    }

    private function clearRenderedPageCache(): void
    {
        foreach (glob(storage_path('app/cache/page-rendered/*')) ?: [] as $file) {
            @unlink($file);
        }
    }
}
