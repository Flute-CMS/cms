<?php

namespace Flute\Core\Admin\Services;

use Flute\Core\Admin\Builders\AdminSidebarBuilder;

class BladeFileParser
{
    private $directory;
    private $cache;
    protected const CACHE_KEY = "flute.admin.parser";
    private $targetPhrase;

    public function __construct($directory, $targetPhrase = null)
    {
        $this->directory = $directory;
        $this->targetPhrase = $targetPhrase;
    }

    /**
     * Рекурсивно обходит директорию и возвращает список всех файлов.
     */
    private function getFilesRecursively()
    {
        return finder()->files()->in($this->directory)->name('*.blade.php');
    }

    /**
     * Ищет все фразы в admin-header > h2, admin-header > p, .col-form-label > label и кеширует результаты.
     *
     * @return void
     */
    public function cachePhrases()
    {
        $files = $this->getFilesRecursively();
        $result = [];

        foreach ($files as $file) {
            $content = file_get_contents($file->getRealPath());

            if ($file->getFilename() === 'edit.blade.php')
                continue;

            // Ищем фразы в admin-header > h2
            if (preg_match_all('/<div[^>]*class="[^"]*admin-header[^"]*"[^>]*>.*?<h2[^>]*>.*?@t\(([^)]+)\).*?<\/h2>/s', $content, $matches)) {
                foreach ($matches[1] as $match) {
                    $result[$file->getRealPath()][] = __($this->stripQuotes($match));
                }
            }

            if (preg_match_all('/<div[^>]*class="[^"]*col-form-label[^"]*"[^>]*>\s*<label[^>]*>(.*?)<\/label>/s', $content, $labelMatches)) {
                foreach ($labelMatches[1] as $labelContent) {
                    if (preg_match('/@t\((\'[^\']+\'|"[^"]+")\)/', $labelContent, $tMatches)) {
                        $result[$file->getRealPath()][] = __($this->stripQuotes($tMatches[1]));
                    }
                }
            }
        }

        cache()->set(self::CACHE_KEY, $result);
    }

    /**
     * Удаляет кавычки из строки.
     *
     * @param string $string
     * @return string
     */
    private function stripQuotes($string)
    {
        return trim($string, "'\"");
    }

    /**
     * Загружает кешированные фразы.
     *
     * @return array
     */
    private function loadCache()
    {
        if (!cache()->has(self::CACHE_KEY)) {
            $this->cachePhrases();
        }

        return cache()->get(self::CACHE_KEY, []);
    }

    /**
     * Ищет фразы в кеше по заданной фразе.
     *
     * @return array
     */
    public function searchPhrasesInCache()
    {
        $cache = $this->loadCache();
        $result = [];

        foreach ($cache as $file => $phrases) {
            foreach ($phrases as $phrase) {
                if (strpos(mb_strtolower($phrase), mb_strtolower($this->targetPhrase)) !== false) {
                    $result[$file][] = $phrase;
                }
            }
        }

        return $result;
    }

    /**
     * Возвращает ассоциации фраз с файлами.
     *
     * @param array $filesWithPhrases
     * @return array
     */
    public function getAssociations($filesWithPhrases)
    {
        $associations = [];
        foreach ($filesWithPhrases as $file => $phrases) {
            foreach ($phrases as $phrase) {
                $associations[] = [
                    'file' => $file,
                    'phrase' => $phrase,
                    'association' => $this->getAssociation($file)
                ];
            }
        }
        return $associations;
    }

    /**
     * Возвращает ассоциацию файла на основе его директории и title из $items.
     *
     * @param string $file
     * @return array
     */
    private function getAssociation($file)
    {
        $base = str_replace('\\', '/', str_replace(['public\\..\\', 'public/../'], '', BASE_PATH));

        $normalizedPath = str_replace('\\', '/', $file);
        $relativePath = str_replace([$base, 'app/Core/Admin/Http/Views/pages/', '.blade.php'], '', $normalizedPath);
        $relativePath = '/admin/' . ltrim($relativePath, '/');

        if (str_contains($normalizedPath, 'pages/main/items/')) {
            return ['path' => '/admin/settings', 'association' => __('admin.main_settings.header')];
        }

        if (str_contains($normalizedPath, 'pages/payments/promo')) {
            return ['path' => $relativePath, 'association' => __('admin.payments.promo.header')];
        }

        if (str_contains($normalizedPath, 'pages/footer/social')) {
            return ['path' => $relativePath, 'association' => __('admin.footer.social_header')];
        }

        // Получаем название директории из пути файла
        $directory = basename(dirname($normalizedPath));

        foreach (AdminSidebarBuilder::all() as $section => $elements) {
            foreach ($elements as $element) {
                if (isset($element['items'])) {
                    foreach ($element['items'] as $subElement) {
                        if (strpos($subElement['url'], 'admin/' . $directory) !== false) {
                            return ['path' => $relativePath, 'association' => __($subElement['title'])];
                        }
                    }
                } else {
                    if (strpos($element['url'], 'admin/' . $directory) !== false) {
                        return ['path' => $relativePath, 'association' => __($element['title'])];
                    }
                }
            }
        }

        return ['path' => $relativePath, 'association' => 'Неизвестная ассоциация'];
    }
}