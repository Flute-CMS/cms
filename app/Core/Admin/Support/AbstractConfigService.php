<?php

namespace Flute\Core\Admin\Support;

use Flute\Core\Services\FileSystemService;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractConfigService
{
    protected const CONFIGS = BASE_PATH . 'config/%s.php';

    protected FileSystemService $fileSystemService;

    public function __construct(FileSystemService $fileSystemService)
    {
        $this->fileSystemService = $fileSystemService;
    }

    abstract public function updateConfig(array $params): Response;

    protected function b(string $str): bool
    {
        return filter_var($str, FILTER_VALIDATE_BOOLEAN);
    }

    protected function getConfigPath(string $name): string
    {
        return sprintf(self::CONFIGS, $name);
    }
}
