<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\DatabaseConnection;
use Flute\Core\Modules\ModuleActions;
use Flute\Core\Modules\ModuleManager;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Flute\Core\Theme\ThemeManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

class ModulesController extends AbstractController
{
    protected ModuleManager $moduleManager;
    protected ModuleActions $actions;

    public function __construct(ModuleManager $moduleManager, ModuleActions $moduleActions)
    {
        HasPermissionMiddleware::permission('admin.modules');

        $this->moduleManager = $moduleManager;
        $this->actions = $moduleActions;
    }

    public function changeSettings(FluteRequest $request, string $key)
    {
        $providers = $request->input('providers');

        if (!$this->moduleManager->issetModule($key))
            return $this->error(__('admin.modules_list.module_is_not_exists', ['key' => $key]));

        foreach ($providers as $providerKey => $provider) {
            $provider = "Flute\\Modules\\{$provider}";

            if (!class_exists($provider))
                return $this->error(__('admin.modules_list.sp_error', ['provider' => pathinfo($provider)['basename']]));

            $providers[$providerKey] = $provider;
        }

        $module = $this->moduleManager->getModule($key);
        $moduleJson = $this->moduleManager->getModuleJson($key);

        unset($module->status);
        $module->providers = $providers;

        fs()->dumpFile($moduleJson, \Nette\Utils\Json::encode($module, JSON_PRETTY_PRINT));

        user()->log('events.module_settings_edited', $key);

        return $this->success(__('def.success'));
    }

    public function enable(FluteRequest $request, string $key): Response
    {
        try {
            $mopd = $this->moduleManager->getModule($key);

            $this->actions->activateModule($mopd);
            user()->log('events.module_activated', $key);

            cache()->delete('flute.deferred_listeners');
            cache()->delete(DatabaseConnection::CACHE_KEY);

            return $this->success();
        } catch (\Exception $e) {
            logs()->error($e);
            return $this->error($e->getMessage());
        }
    }

    public function disable(FluteRequest $request, string $key): Response
    {
        try {
            $mopd = $this->moduleManager->getModule($key);
            user()->log('events.module_disabled', $key);

            $this->actions->disableModule($mopd);

            cache()->delete('flute.deferred_listeners');

            return $this->success();
        } catch (\Exception $e) {
            logs()->error($e);
            return $this->error($e->getMessage());
        }
    }

    public function install(FluteRequest $request, string $key): Response
    {
        try {
            $mopd = $this->moduleManager->getModule($key);

            if (!$this->actions->installModule($mopd))
                return $this->error(__('def.unknown_error'));

            cache()->delete('flute.deferred_listeners');

            user()->log('events.module_installed', $key);

            return $this->success();
        } catch (\Exception $e) {
            logs()->error($e);
            return $this->error($e->getMessage());
        }
    }

    public function update(FluteRequest $request, string $key): Response
    {
        try {
            $mopd = $this->moduleManager->getModule($key);

            if (!$this->actions->updateModule($mopd))
                return $this->error(__('def.unknown_error'));

            cache()->delete('flute.deferred_listeners');

            user()->log('events.module_installed', $key);

            return $this->success();
        } catch (\Exception $e) {
            logs()->error($e);
            return $this->error($e->getMessage());
        }
    }

    /**
     * Handles the module ZIP file upload.
     * 
     * @param FluteRequest $request
     * @return Response
     */
    public function installFirst(FluteRequest $request): Response
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('file');

        if ($file === null) {
            return $this->error(__('admin.modules_list.upload_zip_required'));
        }

        if ($file->getClientMimeType() !== 'application/zip' && $file->getClientMimeType() !== 'application/x-zip-compressed') {
            return $this->error(__('admin.modules_list.invalid_zip'));
        }

        $maxSize = 10 * 1000000;
        if ($file->getSize() > $maxSize) {
            return $this->error(__('admin.modules_list.max_zip_size', ['%d' => $maxSize]));
        }

        return $this->processZipFile($file);
    }

    /**
     * Process and validate the ZIP file.
     * 
     * @param UploadedFile $file
     * @return Response
     */
    protected function processZipFile(UploadedFile $file): Response
    {
        $zip = new ZipArchive;
        if ($zip->open($file->getPathname()) !== true) {
            return $this->error(__('admin.modules_list.zip_open_failed'));
        }

        $rootFolders = $this->getRootFolders($zip);
        if (count($rootFolders) !== 1) {
            $zip->close();
            return $this->error(__('admin.modules_list.single_folder_expected'));
        }

        // Remove '-main' suffix from the folder name
        $originalFolderName = reset($rootFolders);
        $folderName = preg_replace('/(-main|-[\d.]+|_test)$/', '', $originalFolderName);
        $tempExtractPath = BASE_PATH . 'storage/app/temp/' . $folderName;

        // Ensure temp directory exists
        if (!fs()->exists($tempExtractPath)) {
            fs()->mkdir($tempExtractPath, 0755);
        }

        // Extract ZIP contents into the temp directory
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $newFilename = preg_replace('/^' . preg_quote($originalFolderName, '/') . '/', $folderName, $filename);
            $this->extractFile($zip, $filename, $tempExtractPath . '/' . $newFilename);
        }

        $zip->close();

        // Navigate into the extracted module folder
        $modulePath = $tempExtractPath . '/';
        $innerFolderPath = $modulePath . $folderName . '/';
        $moduleJsonPath = $innerFolderPath . 'module.json';

        // Check if module.json exists in the inner folder
        if (!fs()->exists($moduleJsonPath)) {
            // If not, fallback to checking the outer folder
            $moduleJsonPath = $modulePath . 'module.json';
            $innerFolderPath = $modulePath; // Adjust the inner folder path accordingly
        }

        if (!fs()->exists($moduleJsonPath)) {
            fs()->remove($modulePath);
            return $this->error(__('admin.modules_list.module_json_not_found'));
        }

        $moduleConfig = json_decode(file_get_contents($moduleJsonPath), true);
        if (!$moduleConfig) {
            fs()->remove($modulePath);
            return $this->error(__('admin.modules_list.invalid_module_json'));
        }

        try {
            if (isset($moduleConfig['dependencies']))
                $this->moduleManager->getModuleDependencies()->checkDependencies(
                    $moduleConfig['dependencies'],
                    $this->moduleManager->getActive(),
                    app(ThemeManager::class)->getThemeInfo()
                );
        } catch (\Exception $e) {
            fs()->remove($modulePath);

            return $this->json([
                'moduleName' => $folderName,
                'errors' => [
                    $e->getMessage()
                ]
            ]);
        }

        $newVersion = $moduleConfig['version'];

        $extractPath = BASE_PATH . 'app/Modules/' . $folderName;

        if ($this->moduleManager->issetModule($folderName)) {
            $currentVersion = $this->moduleManager->getModule($folderName)->version;

            if (version_compare($newVersion, $currentVersion, '>')) {

                $mopd = $this->moduleManager->getModule($folderName);

                try {
                    $this->actions->disableModule($mopd);
                } catch (\Exception $e) {
                    //
                }

                $this->updateModuleFiles($extractPath, $innerFolderPath);
                fs()->remove($modulePath);
                return $this->json([
                    'moduleName' => $folderName,
                    'moduleVersion' => $newVersion,
                    'type' => 'update',
                ]);
            } else {
                fs()->remove($modulePath);
                return $this->error(__('admin.modules_list.no_new_version'));
            }
        } else {
            if (!fs()->exists($extractPath)) {
                fs()->rename($innerFolderPath, $extractPath); // Move the inner folder to the modules directory
            } else {
                fs()->remove($modulePath);
                return $this->error(__('admin.modules_list.module_already_exists'));
            }
        }

        cache()->delete('flute.modules.alldb');
        cache()->delete('flute.modules.array');
        cache()->delete('flute.modules.json');
        cache()->delete('flute.deferred_listeners');

        user()->log('events.module_uploaded', $folderName);

        return $this->json([
            'moduleName' => $folderName,
            'moduleVersion' => $newVersion,
            'type' => 'install',
        ]);
    }

    /**
     * Update the module files, excluding the config directory.
     *
     * @param string $destination
     * @param string $source
     */
    protected function updateModuleFiles(string $destination, string $source)
    {
        $dir = opendir($source);
        if (!fs()->exists($destination)) {
            fs()->mkdir($destination, 0755);
        }

        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                $srcFile = $source . '/' . $file;
                $destFile = $destination . '/' . $file;

                if (is_dir($srcFile)) {
                    if ($file !== 'config') {
                        $this->updateModuleFiles($destFile, $srcFile);
                    }
                } else {
                    fs()->copy($srcFile, $destFile);
                }
            }
        }
        closedir($dir);
    }

    /**
     * Extracts a single file from a ZipArchive.
     *
     * @param ZipArchive $zip
     * @param string $filename
     * @param string $destination
     */
    protected function extractFile($zip, $filename, $destination)
    {
        $fileStream = $zip->getStream($filename);
        if (!$fileStream) {
            throw new \Exception("Unable to retrieve stream for file $filename in ZIP archive.");
        }

        if (is_dir($filename))
            return;

        $dir = dirname($destination);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($destination, $fileStream);
        fclose($fileStream);
    }

    /**
     * Get the root folders in the ZIP archive.
     * 
     * @param ZipArchive $zip
     * @return array
     */
    protected function getRootFolders(ZipArchive $zip): array
    {
        $folders = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (strpos($filename, '/') !== false) {
                $folder = rtrim(explode('/', $filename)[0], '/');
                $folders[$folder] = true;
            }
        }
        return array_keys($folders);
    }

    public function delete(FluteRequest $request, string $key): Response
    {
        try {
            $mopd = $this->moduleManager->getModule($key);

            $this->actions->uninstallModule($mopd);

            cache()->delete('flute.modules.alldb');
            cache()->delete('flute.modules.array');
            cache()->delete('flute.modules.json');
            cache()->delete('flute.deferred_listeners');

            user()->log('events.module_deleted', $key);

            return $this->success();
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
