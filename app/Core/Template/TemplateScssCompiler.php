<?php

namespace Flute\Core\Template;

use ScssPhp\ScssPhp\Compiler;

/**
 * Small wrapper over scssphp Compiler that lets TemplateAssets remember the "base" import paths
 * set by external callers (admin/installer). This prevents TemplateAssets from unintentionally
 * wiping import paths during compilation.
 */
class TemplateScssCompiler extends Compiler
{
    /**
     * @var array<int, mixed>
     */
    private array $baseImportPaths = [];

    /**
     * Keep a copy of import paths provided by external code.
     *
     * Signature intentionally left untyped for compatibility with scssphp.
     *
     * @param mixed $importPaths
     */
    public function setImportPaths($importPaths)
    {
        $this->baseImportPaths = is_array($importPaths) ? array_values($importPaths) : [$importPaths];

        parent::setImportPaths($importPaths);
    }

    /**
     * Track appended import paths too (best-effort).
     *
     * Signature intentionally left untyped for compatibility with scssphp.
     *
     * @param mixed $path
     */
    public function addImportPath($path)
    {
        $this->baseImportPaths[] = $path;

        parent::addImportPath($path);
    }

    /**
     * @return array<int, mixed>
     */
    public function getBaseImportPaths(): array
    {
        return $this->baseImportPaths;
    }
}
