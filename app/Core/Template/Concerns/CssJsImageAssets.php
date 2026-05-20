<?php

namespace Flute\Core\Template\Concerns;

use Nette\Utils\Validators;
use Throwable;
use WebPConvert\WebPConvert;

trait CssJsImageAssets
{
    protected function generateTag(string $url, string $type): string
    {
        $escapedUrl = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        switch ($type) {
            case 'css':
                return "<link href=\"{$escapedUrl}\" rel=\"stylesheet\">";
            case 'js':
                return "<script src=\"{$escapedUrl}\" defer></script>";
            case 'img':
                return "<img src=\"{$escapedUrl}\" alt=\"\" loading=\"lazy\" decoding=\"async\">";
            default:
                return '';
        }
    }

    private function processCssAsset(string $expression, string $cssPathBase): string
    {
        if (Validators::isUrl($expression)) {
            return $this->processRemoteAsset($expression, 'css');
        }

        if (!file_exists($cssPathBase)) {
            return '';
        }

        $hash = sha1($cssPathBase);
        $cssPath = self::CSS_CACHE_DIR . "{$hash}.css";
        $cssFullPath = BASE_PATH . 'public/' . $cssPath;

        if (!file_exists($cssFullPath) || filemtime($cssPathBase) > filemtime($cssFullPath)) {
            $content = file_get_contents($cssPathBase);
            if ($content === false) {
                logs()->error("Unable to read CSS file: {$cssPathBase}");

                return '';
            }
            $this->saveAsset($cssFullPath, $content);
        }

        $version = filemtime($cssFullPath);
        $url = url($cssPath) . "?v={$version}";

        return "<link href=\"{$url}\" rel=\"stylesheet\">";
    }

    private function processJsAsset(string $expression, string $jsPathBase, bool $urlOnly = false): string
    {
        if (Validators::isUrl($expression)) {
            return $this->processRemoteAsset($expression, 'js');
        }

        if (!file_exists($jsPathBase)) {
            $pathParts = explode('/', $expression);
            if (count($pathParts) >= 4 && $pathParts[0] === 'Themes') {
                $relativePath = implode('/', array_slice($pathParts, 4));
                $fallbackPath = $this->findAssetWithFallback($relativePath, 'scripts');
                if ($fallbackPath) {
                    $jsPathBase = $fallbackPath;
                } else {
                    return '';
                }
            } else {
                return '';
            }
        }

        $hash = sha1($jsPathBase);
        $jsPath = self::JS_CACHE_DIR . "{$hash}.js";
        $jsFullPath = BASE_PATH . 'public/' . $jsPath;

        if (!file_exists($jsFullPath) || filemtime($jsPathBase) > filemtime($jsFullPath)) {
            $lockFile = $jsFullPath . '.lock';
            $this->withFileLock($lockFile, function () use ($jsPathBase, $jsFullPath) {
                if (file_exists($jsFullPath) && filemtime($jsPathBase) <= filemtime($jsFullPath)) {
                    return;
                }

                $content = file_get_contents($jsPathBase);
                if ($content === false) {
                    logs()->error("Unable to read JS file: {$jsPathBase}");

                    return;
                }
                $this->saveAsset($jsFullPath, $content);
            });
        }

        $version = filemtime($jsFullPath);
        $url = url($jsPath) . "?v={$version}";

        return $urlOnly ? $url : "<script src=\"{$url}\" defer></script>";
    }

    private function processImageAsset(
        string $expression,
        string $imgPathBase,
        string $extension,
        bool $urlOnly = false,
    ): string {
        if (Validators::isUrl($expression)) {
            return $this->processRemoteAsset($expression, 'img');
        }

        if (!file_exists($imgPathBase) || !in_array($extension, self::SUPPORTED_IMAGE_EXTENSIONS)) {
            $pathParts = explode('/', $expression);
            if (count($pathParts) >= 4 && $pathParts[0] === 'Themes') {
                $relativePath = implode('/', array_slice($pathParts, 4));
                $fallbackPath = $this->findAssetWithFallback($relativePath, 'images');
                if ($fallbackPath && in_array($extension, self::SUPPORTED_IMAGE_EXTENSIONS)) {
                    $imgPathBase = $fallbackPath;
                } else {
                    return '';
                }
            } else {
                return '';
            }
        }

        $hash = $this->debugMode ? pathinfo($expression, PATHINFO_FILENAME) : sha1($expression);
        $imgPath = self::IMG_CACHE_DIR . "{$hash}.{$extension}";
        $imgFullPath = BASE_PATH . 'public/' . $imgPath;

        if (in_array($extension, ['png', 'jpg', 'jpeg']) && config('app.convert_to_webp')) {
            $webpPath = self::IMG_CACHE_DIR . "{$hash}.webp";
            $webpFullPath = BASE_PATH . 'public/' . $webpPath;

            if (!file_exists($webpFullPath) || filemtime($imgPathBase) > filemtime($webpFullPath)) {
                $lockFile = $webpFullPath . '.lock';

                try {
                    $this->withFileLock($lockFile, static function () use ($imgPathBase, $webpFullPath) {
                        if (file_exists($webpFullPath) && filemtime($imgPathBase) <= filemtime($webpFullPath)) {
                            return;
                        }

                        WebPConvert::convert($imgPathBase, $webpFullPath);
                    });
                } catch (Throwable $e) {
                    logs()->error($e->getMessage());

                    return $this->generateAssetUrl($imgPath);
                }
            }

            $imgPath = $webpPath;
            $imgFullPath = $webpFullPath;
        }

        if (!file_exists($imgFullPath) || filemtime($imgPathBase) > filemtime($imgFullPath)) {
            $this->copyAsset($imgPathBase, $imgFullPath);
        }

        return $urlOnly ? url($imgPath) : '<img src="' . url($imgPath) . '" alt="" loading="lazy">';
    }
}
