<?php

namespace Flute\Core\Modules\Installer\Services;

class SystemRequirements
{
    /**
     * Minimum PHP version required
     *
     * @var string
     */
    protected $minPhpVersion = '8.2.0';

    /**
     * Required PHP extensions
     *
     * @var array
     */
    protected $requiredExtensions = [
        'curl',
        'fileinfo',
        'intl',
        'json',
        'mbstring',
        'openssl',
        'pdo',
        'tokenizer',
        'xml',
        'zip',
        'gmp',
        'bcmath',
    ];

    /**
     * Directories that need to be writable
     *
     * @var array
     */
    protected $writableDirectories = [
        'storage',
        'storage/logs',
        'storage/app/cache',
        'public/assets/uploads',
        'storage/backup',
        'config',
        'i18n',
    ];

    /**
     * Check PHP version requirements
     */
    public function checkPhpRequirements(): array
    {
        $currentVersion = phpversion();
        $check = version_compare($currentVersion, $this->minPhpVersion, '>=');

        return [
            [
                'name' => 'PHP',
                'current' => $currentVersion,
                'required' => $this->minPhpVersion,
                'status' => $check,
            ],
        ];
    }

    /**
     * Check PHP extension requirements
     */
    public function checkExtensionRequirements(): array
    {
        $results = [];

        foreach ($this->requiredExtensions as $extension) {
            $results[] = [
                'name' => $extension,
                'current' => extension_loaded($extension) ? __('install.requirements.installed') : __('install.requirements.not_installed'),
                'required' => __('install.requirements.installed'),
                'status' => extension_loaded($extension),
            ];
        }

        return $results;
    }

    /**
     * Check directory permission requirements
     */
    public function checkDirectoryRequirements(): array
    {
        $results = [];
        $basePath = path();

        foreach ($this->writableDirectories as $directory) {
            $path = $basePath.'/'.$directory;

            // Create directory if it doesn't exist
            if (!file_exists($path)) {
                @mkdir($path, 0o755, true);
            }

            $isWritable = is_writable($path);

            $results[] = [
                'name' => $directory,
                'current' => $isWritable ? __('install.requirements.writable') : __('install.requirements.not_writable'),
                'required' => __('install.requirements.writable'),
                'status' => $isWritable,
            ];
        }

        return $results;
    }

    /**
     * Check if all requirements are met
     */
    public function allRequirementsMet(): bool
    {
        foreach ($this->checkPhpRequirements() as $check) {
            if (!$check['status']) {
                return false;
            }
        }

        foreach ($this->checkExtensionRequirements() as $check) {
            if (!$check['status']) {
                return false;
            }
        }

        foreach ($this->checkDirectoryRequirements() as $check) {
            if (!$check['status']) {
                return false;
            }
        }

        return true;
    }
}
