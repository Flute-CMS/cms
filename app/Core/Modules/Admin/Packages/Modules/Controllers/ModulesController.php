<?php

namespace Flute\Admin\Packages\Modules\Controllers;

use Exception;
use Flute\Core\ModulesManager\ModuleActions;
use Flute\Core\ModulesManager\ModuleInformation;
use Flute\Core\ModulesManager\ModuleManager;
use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;
use ZipArchive;

class ModulesController extends BaseController
{
    /**
     * Maximum allowed module archive size (50MB)
     */
    public const MAX_FILE_SIZE = 52428800;

    /**
     * Allowed mime types for module archives
     */
    public const ALLOWED_MIME_TYPES = [
        'application/zip',
        'application/x-zip-compressed',
        'application/octet-stream', // Some systems may use this for zip files
    ];

    /**
     * Required files in a valid module
     */
    public const REQUIRED_MODULE_FILES = [
        'module.json',
    ];

    /**
     * Required fields in module.json
     */
    public const REQUIRED_MODULE_JSON_FIELDS = [
        'name',
        'version',
    ];

    /**
     * Handler for module installation via file upload
     *
     * @param FluteRequest $request
     */
    public function installModule(FluteRequest $request)
    {
        try {
            // Check if file was uploaded
            if (!isset($_FILES['module_archive']) || $_FILES['module_archive']['error'] !== UPLOAD_ERR_OK) {
                return $this->error(__('admin-modules.dropzone.errors.no_file'), 400);
            }

            $file = $_FILES['module_archive'];

            // Security: Check file size
            if ($file['size'] > self::MAX_FILE_SIZE) {
                return $this->error(__('admin-modules.dropzone.errors.file_too_large'), 400);
            }

            // Security: Validate file type
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($fileExt !== 'zip') {
                return $this->error(__('admin-modules.dropzone.errors.invalid_file'), 400);
            }

            // Security: Check mime type
            $fileMimeType = $this->getFileMimeType($file['tmp_name']);
            if (!in_array($fileMimeType, self::ALLOWED_MIME_TYPES)) {
                logs()->warning("Invalid module mime type attempted: {$fileMimeType}");

                return $this->error(__('admin-modules.dropzone.errors.invalid_file'), 400);
            }

            $tempPath = $this->prepareTemporaryDirectory('app/temp/modules');
            $extractPath = $this->prepareTemporaryDirectory('app/temp/modules/extract_' . time());

            // Generate unique filename and move uploaded file
            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9\.\-\_]/', '', $file['name']);
            $filePath = $tempPath . '/' . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                return $this->error(__('admin-modules.dropzone.errors.upload_failed'), 500);
            }

            // Security: Verify zip file integrity
            if (!$this->isValidZipArchive($filePath)) {
                $this->cleanupTempFiles($extractPath, $filePath);

                return $this->error(__('admin-modules.dropzone.errors.invalid_zip'), 400);
            }

            // Extract the archive
            if (!$this->extractArchive($filePath, $extractPath)) {
                return $this->error(__('admin-modules.dropzone.errors.extract_failed'), 500);
            }

            // Find module directory
            $moduleDir = $this->findModuleDirectory($extractPath);
            if (!$moduleDir) {
                $this->cleanupTempFiles($extractPath, $filePath);

                return $this->error(__('admin-modules.dropzone.errors.invalid_structure'), 400);
            }

            // Validate module structure
            if (!$this->validateModuleStructure($moduleDir)) {
                $this->cleanupTempFiles($extractPath, $filePath);

                return $this->error(__('admin-modules.dropzone.errors.invalid_structure'), 400);
            }

            // Read and validate module.json
            $moduleJsonPath = $moduleDir . '/module.json';
            $moduleInfo = $this->validateModuleJson($moduleJsonPath);
            if (!$moduleInfo) {
                $this->cleanupTempFiles($extractPath, $filePath);

                return $this->error(__('admin-modules.dropzone.errors.invalid_module_json'), 400);
            }

            // Security: Make sure module key contains only valid characters
            $moduleKey = $moduleInfo['name'];
            if (!preg_match('/^[a-zA-Z0-9\-\_]+$/', $moduleKey)) {
                $this->cleanupTempFiles($extractPath, $filePath);

                return $this->error(__('admin-modules.dropzone.errors.invalid_module_key'), 400);
            }

            // Prepare for installation
            $modulesPath = path('app/Modules');

            // Create a backup if module already exists
            if (file_exists($modulesPath . '/' . $moduleKey)) {
                $this->backupExistingModule($moduleKey);
            }

            // Copy module to modules directory
            $targetDir = $modulesPath . '/' . $moduleKey;
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0o755, true);
            }
            $this->recursiveCopy($moduleDir, $targetDir);

            // Install the module
            $moduleManager = app(ModuleManager::class);
            $moduleManager->refreshModules();

            // Check if module is now registered
            if (!$moduleManager->issetModule($moduleKey)) {
                // Create a temporary module information object if not registered
                $moduleObj = $this->createTempModuleInfo($moduleInfo);
            } else {
                $moduleObj = $moduleManager->getModule($moduleKey);
            }

            // Install the module
            app(ModuleActions::class)->installModule($moduleObj, $moduleManager);

            // Clean up temporary files
            $this->cleanupTempFiles($extractPath, $filePath);

            // Success!
            $moduleName = $moduleInfo['name'] ?? $moduleKey;

            return response()->json([
                'success' => true,
                'message' => __('admin-modules.messages.installed', ['name' => $moduleName]),
            ]);

        } catch (Exception $e) {
            logs('modules')->error('Module installation error: ' . $e->getMessage());

            return $this->error(__('admin-modules.dropzone.errors.installation_failed', ['error' => $e->getMessage()]), 500);
        }
    }

    /**
     * Check if the file is a valid ZIP archive
     *
     * @param string $filePath
     * @return bool
     */
    protected function isValidZipArchive($filePath)
    {
        $zip = new ZipArchive();
        $result = $zip->open($filePath);

        if ($result === true) {
            // Check for potential zip bombs
            $totalSize = 0;
            $maxSize = 100 * 1024 * 1024; // 100MB max extracted size

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stats = $zip->statIndex($i);
                $totalSize += $stats['size'];

                // Check for suspiciously large compression ratio
                if ($stats['size'] > 0 && ($stats['comp_size'] / $stats['size']) < 0.001) {
                    logs('security')->warning("Potential zip bomb detected with high compression ratio");
                    $zip->close();

                    return false;
                }

                // Check if total extracted size would exceed limit
                if ($totalSize > $maxSize) {
                    logs('security')->warning("Potential zip bomb detected with large total size");
                    $zip->close();

                    return false;
                }
            }

            $zip->close();

            return true;
        }

        return false;
    }

    /**
     * Extract the archive
     *
     * @param string $filePath
     * @param string $extractPath
     * @return bool
     */
    protected function extractArchive($filePath, $extractPath)
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return false;
        }

        $success = $zip->extractTo($extractPath);
        $zip->close();

        return $success;
    }

    /**
     * Check if module structure is valid
     *
     * @param string $moduleDir
     * @return bool
     */
    protected function validateModuleStructure($moduleDir)
    {
        foreach (self::REQUIRED_MODULE_FILES as $requiredFile) {
            if (!file_exists($moduleDir . '/' . $requiredFile)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate module.json contents
     *
     * @param string $moduleJsonPath
     * @return array|false
     */
    protected function validateModuleJson($moduleJsonPath)
    {
        if (!file_exists($moduleJsonPath)) {
            return false;
        }

        $content = file_get_contents($moduleJsonPath);
        $moduleInfo = json_decode($content, true);

        if (!$moduleInfo || !is_array($moduleInfo)) {
            return false;
        }

        // Check for required fields
        foreach (self::REQUIRED_MODULE_JSON_FIELDS as $field) {
            if (!isset($moduleInfo[$field]) || empty($moduleInfo[$field])) {
                return false;
            }
        }

        return $moduleInfo;
    }

    /**
     * Create a backup of existing module
     *
     * @param string $moduleKey
     * @return void
     */
    protected function backupExistingModule($moduleKey)
    {
        if (!config('app.create_backup')) {
            return;
        }

        $modulesPath = path('app/Modules');
        $backupDir = storage_path('backup/modules/' . $moduleKey . '_' . date('Y-m-d_H-i-s'));

        if (!file_exists(dirname($backupDir))) {
            mkdir(dirname($backupDir), 0o755, true);
        }

        $this->recursiveCopy($modulesPath . '/' . $moduleKey, $backupDir);
    }

    /**
     * Prepare a temporary directory
     *
     * @param string $relativePath
     * @return string
     */
    protected function prepareTemporaryDirectory($relativePath)
    {
        $path = storage_path($relativePath);
        if (!file_exists($path)) {
            mkdir($path, 0o755, true);
        }

        return $path;
    }

    /**
     * Get mime type of the file
     *
     * @param string $filePath
     * @return string
     */
    protected function getFileMimeType($filePath)
    {
        // Use finfo for more reliable mime detection
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $filePath);
            finfo_close($finfo);

            return $mime;
        }

        // Fallback to mime_content_type
        if (function_exists('mime_content_type')) {
            return mime_content_type($filePath);
        }

        // Last resort fallback based on extension
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($ext === 'zip') {
            return 'application/zip';
        }

        return 'application/octet-stream';
    }

    /**
     * Find the module directory in extracted files
     *
     * @param string $extractPath
     * @return string|null
     */
    protected function findModuleDirectory($extractPath)
    {
        if (file_exists($extractPath . '/module.json')) {
            return $extractPath;
        }

        $directories = glob($extractPath . '/*', GLOB_ONLYDIR);
        foreach ($directories as $dir) {
            if (file_exists($dir . '/module.json')) {
                return $dir;
            }
        }

        return null;
    }

    /**
     * Create a temporary ModuleInformation object from module info array
     *
     * @param array $moduleInfo
     * @return ModuleInformation
     */
    protected function createTempModuleInfo($moduleInfo)
    {
        $module = new ModuleInformation(
            $moduleInfo['key'],
            $moduleInfo['name'] ?? $moduleInfo['key'],
        );

        return $module;
    }

    /**
     * Clean up temporary files
     *
     * @param string $extractPath
     * @param string $filePath
     */
    protected function cleanupTempFiles($extractPath, $filePath)
    {
        if (is_dir($extractPath)) {
            $this->recursiveDelete($extractPath);
        }

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * Recursively copy files from one directory to another
     *
     * @param string $src
     * @param string $dst
     */
    protected function recursiveCopy($src, $dst)
    {
        $dir = opendir($src);
        if (!file_exists($dst)) {
            mkdir($dst, 0o755, true);
        }

        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $srcFile = $src . '/' . $file;
            $dstFile = $dst . '/' . $file;

            if (is_dir($srcFile)) {
                $this->recursiveCopy($srcFile, $dstFile);
            } else {
                copy($srcFile, $dstFile);
            }
        }

        closedir($dir);
    }

    /**
     * Recursively delete directory and its contents
     *
     * @param string $dir
     */
    protected function recursiveDelete($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object === '.' || $object === '..') {
                continue;
            }

            $path = $dir . '/' . $object;

            if (is_dir($path)) {
                $this->recursiveDelete($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
