<?php

namespace Flute\Core\PhosphorIcons;

class PhosphorIconsParser
{
    protected array $icons = [];
    protected const FILE_PATH = "app/Core/PhosphorIcons/icons.json";
    protected const CACHE_KEY = "flute.phosphoricons";

    public function getAll()
    {
        if (empty($this->icons))
            $this->parseIcons();

        return $this->icons;
    }

    protected function parseIcons()
    {
        if (cache()->has(self::CACHE_KEY))
            $this->icons = cache()->get(self::CACHE_KEY);

        $file = path(self::FILE_PATH);

        if (file_exists($file) && is_readable($file)) {
            $this->icons = json_decode(file_get_contents($file), true);

            cache()->set(self::CACHE_KEY, $this->icons);
        }
    }
}