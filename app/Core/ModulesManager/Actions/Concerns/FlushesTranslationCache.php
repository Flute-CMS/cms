<?php

declare(strict_types=1);

namespace Flute\Core\ModulesManager\Actions\Concerns;

/**
 * Flushes compiled Symfony translation catalogues so that
 * newly installed/updated/removed module translations take effect immediately.
 */
trait FlushesTranslationCache
{
    protected function flushCompiledTranslations(): void
    {
        $dir = storage_path('app/translations');

        if (!is_dir($dir)) {
            return;
        }

        $files = glob($dir . '/catalogue.*.php*');

        if ($files) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }
}
