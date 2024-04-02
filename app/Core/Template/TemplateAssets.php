<?php

namespace Flute\Core\Template;

use ScssPhp\ScssPhp\Compiler;
use MatthiasMullie\Minify;
use ScssPhp\ScssPhp\Exception\CompilerException;
use ScssPhp\ScssPhp\Exception\SassException;

class TemplateAssets
{
    protected Compiler $scssCompiler;

    /**
     * Adds a custom @scss directive in Blade for compiling SCSS to CSS and connecting it in templates.
     *
     * @param Template $viewService Instance of the Template class.
     */
    public function addScssDirective(Template $viewService): void
    {
        $viewService->addFunction("at", function ($expression) {
            if (!is_null($expression))
                $this->assetFunction($expression);
        });
    }

    public function rAssetFunction(string $expression): string
    {
        ob_start();
        $this->assetFunction($expression);
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    public function assetFunction(string $expression)
    {
        $filePath = strpos($expression, BASE_PATH) !== false ? $expression : BASE_PATH . "app/" . $expression;

        if ($path = parse_url($expression, PHP_URL_PATH)) {
            $extension = pathinfo($path, PATHINFO_EXTENSION);
        } else {
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        }

        $pathExploded = explode("/", $expression);
        $firstKey = $pathExploded[array_key_first($pathExploded)];

        if ($firstKey === "assets") {
            echo url($expression);
            return '';
        }

        $this->processAssetBasedOnExtension($extension, $expression, $filePath);
    }

    /**
     * Processes the asset based on its extension
     *
     * @param string $extension The file extension
     * @param string $expression The expression (file name)
     * @param string $filePath The path to the file
     */
    private function processAssetBasedOnExtension(string $extension, string $expression, string $filePath): void
    {
        switch ($extension) {
            case 'scss':
                $this->processScssAsset($expression, $filePath);
                break;

            case 'css':
                $this->processCssAsset($expression, $filePath);
                break;

            case 'js':
                $this->processJsAsset($expression, $filePath);
                break;

            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
            case 'webp':
            case 'svg':
                $this->processImageAsset($expression, $filePath, $extension);
                break;

            default:
                // Do nothing
                break;
        }
    }

    private function processScssAsset(string $expression, string $scssPath): void
    {
        if (!file_exists($scssPath)) {
            return;
        }

        // $sha1 = config('app.debug') ? basename($expression, ".scss") : sha1($expression);
        $sha1 = sha1($expression);

        $cssPath = "assets/css/cache/" . $sha1 . ".css";
        $cssBasePath = BASE_PATH . "public/assets/css/cache/" . $sha1 . ".css";

        if (!file_exists($cssBasePath) || (filemtime($scssPath) > filemtime($cssBasePath)) || (bool) config('app.debug') === true) {
            $this->compileScss($scssPath, $cssBasePath);
        }

        // Даже если сработало исключение, мы должны вернуть CSS
        echo sprintf('<link href="%s" rel="stylesheet">', url($cssPath));
    }

    private function processCssAsset(string $expression, string $cssPathBase): void
    {
        if (is_url($expression) && strpos($expression, config('app.url')) !== false) {
            echo sprintf('<link href="%s" rel="stylesheet">', $expression);
            return;
        }

        if (is_url($expression)) {
            $localUrl = $this->processCdnAsset($expression, "css");
            echo sprintf('<link href="%s" rel="stylesheet">', $localUrl);
            return;
        }

        if (!file_exists($cssPathBase)) {
            return;
        }

        $sha1 = config('app.debug') ? basename($expression, ".css") : sha1($expression);

        $cssPath = "assets/css/cache/" . $sha1 . ".css";
        $cssBasePath = BASE_PATH . "public/assets/css/cache/" . $sha1 . ".css";

        if (!file_exists($cssBasePath) || filemtime($cssPathBase) > filemtime($cssBasePath)) {
            $this->saveAsset($cssBasePath, $cssPathBase);
        }

        echo sprintf('<link href="%s" rel="stylesheet">', url($cssPath));
    }

    private function processJsAsset(string $expression, string $jsPathBase): void
    {
        if (is_url($expression) && strpos($expression, config('app.url')) !== false) {
            echo sprintf('<script src="%s" defer></script>', $expression);
            return;
        }

        if (is_url($expression)) {
            $localUrl = $this->processCdnAsset($expression);
            echo sprintf('<script src="%s" defer></script>', $localUrl);
            return;
        }

        if (!file_exists($jsPathBase)) {
            return;
        }

        // $sha1 = config('app.debug') ? basename($expression, ".js") : sha1($expression);
        $sha1 = sha1($expression);

        $jsPath = "assets/js/cache/" . $sha1 . ".js";
        $jsBasePath = BASE_PATH . "public/assets/js/cache/" . $sha1 . ".js";

        if (!file_exists($jsBasePath) || filemtime($jsPathBase) > filemtime($jsBasePath)) {
            $this->saveAsset($jsBasePath, $jsPathBase);
        }

        echo sprintf('<script src="%s" defer></script>', url($jsPath));
    }

    /**
     * Обработка ассета с CDN.
     *
     * @param string $url URL ассета.
     * @param string $type JS / CSS
     * 
     * @return string Локальный путь к ассету.
     */
    protected function processCdnAsset(string $url, string $type = "js"): string
    {
        $hash = sha1($url);

        $extension = pathinfo($url, PATHINFO_EXTENSION);

        if ($path = parse_url($url, PHP_URL_PATH)) {
            $extension = pathinfo($path, PATHINFO_EXTENSION);
        }

        $localPath = "assets/$type/cache/{$hash}." . $extension;
        $fullLocalPath = BASE_PATH . "public/" . $localPath;

        if (!file_exists($fullLocalPath)) {
            $content = file_get_contents($url);
            if ($content !== false) {
                file_put_contents($fullLocalPath, $content);
            }
        }

        return url($localPath);
    }

    private function processImageAsset(string $expression, string $imgPathBase, string $extension): void
    {
        if (is_url($expression) && strpos($expression, config('app.url')) !== false) {
            echo $expression;
            return;
        }

        if (is_url($expression)) {
            $localUrl = $this->processCdnAsset($expression, "img");
            echo $localUrl;
            return;
        }

        if (!file_exists($imgPathBase)) {
            return;
        }

        $sha1 = config('app.debug') ? basename($expression, ".$extension") : sha1($expression);

        $imgPath = "assets/img/cache/" . $sha1 . ".{$extension}";
        $imgBasePath = BASE_PATH . "public/assets/img/cache/" . $sha1 . ".{$extension}";

        // Convert image to WebP format if necessary
        if ($extension === 'png' || $extension === 'jpg' || $extension === 'jpeg') {
            if (config('profile.convert_to_webp')) {
                $webpImgBasePath = BASE_PATH . "public/assets/img/cache/" . $sha1 . ".webp";

                try {
                    \WebPConvert\WebPConvert::convert($imgPathBase, $webpImgBasePath);
                } catch (\Exception $e) {
                    logs()->error($e->getTraceAsString());
                    return;
                }

                $imgBasePath = $webpImgBasePath;
                $imgPath = "assets/img/cache/" . $sha1 . ".webp";
            }
        }

        if (!file_exists($imgBasePath) || filemtime($imgPathBase) > filemtime($imgBasePath)) {
            $this->saveAsset($imgBasePath, @file_get_contents($imgPathBase), false);
        }

        echo url($imgPath);
    }

    /**
     * Получение локального пути из URL.
     *
     * @param string $url URL ассета.
     * @return string Локальный путь к ассету.
     */
    protected function getLocalPathFromUrl(string $url): string
    {
        return BASE_PATH . 'public/' . parse_url($url, PHP_URL_PATH);
    }

    /**
     * @throws SassException
     */
    private function compileScss(string $scssPath, string $cssPath): void
    {
        $compiler = $this->getCompiler();
        $scss = @file_get_contents($scssPath);

        if ($scss) {
            $compiler->addImportPath(
                path(
                    sprintf('app/Themes/%s/assets/styles/', app()->getTheme())
                )
            );
            $compiler->setOutputStyle('compressed');

            try {
                $css = $compiler->compileString($scss)->getCss();

                // У нас изначально CSS в компрессии, поэтому смысла нет его еще раз минимизировать
                $this->saveAsset($cssPath, $css, false);
            } catch (SassException $e) {
                $message = sprintf(
                    "%s, file: %s",
                    $e->getMessage(),
                    str_replace(BASE_PATH, '', $scssPath) // Нам не нужно выводить путь всей нашей машины))
                );

                if (app('app.debug') === true) {
                    throw new CompilerException($message);
                } else {
                    logs()->error($message);
                }
            }
        }
    }

    public function getCompiler(): Compiler
    {
        if (!isset($this->scssCompiler))
            $this->scssCompiler = new Compiler;

        $this->scssCompiler->addVariables(template()->variables()->getAll());

        return $this->scssCompiler;
    }

    protected function saveAsset(string $path, string $oldPath, bool $minify = true): void
    {
        @file_put_contents($path, $minify ? $this->minify_file($oldPath) : $oldPath, LOCK_EX);
    }

    protected function minify_file(string $filename): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        if ($extension === 'js') {
            $content = $this->minifyJs(file_get_contents($filename));
        } elseif ($extension === 'css') {
            $content = $this->minifyCss(file_get_contents($filename));
        } else {
            $content = '';
        }

        return $content;
    }

    protected function minifyJs(string $content): string
    {
        if ((bool) config('assets.cache') === false)
            return $content;

        $minifier = new Minify\JS();
        $min_js = $minifier->add($content);
        return $min_js->minify();
    }
    protected function minifyCss(string $content): string
    {
        if ((bool) config('assets.cache') === false)
            return $content;

        $minifier = new Minify\CSS();
        $min_js = $minifier->add($content);
        return $min_js->minify();
    }
}