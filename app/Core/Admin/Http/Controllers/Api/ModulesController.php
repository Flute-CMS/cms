<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
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
        $providers = $request->get('providers');

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

        return $this->success(__('def.success'));
    }

    public function enable(FluteRequest $request, string $key): Response
    {
        try {
            $mopd = $this->moduleManager->getModule($key);

            $this->actions->activateModule($mopd);

            return $this->success();
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function disable(FluteRequest $request, string $key): Response
    {
        try {
            $mopd = $this->moduleManager->getModule($key);

            $this->actions->disableModule($mopd);

            return $this->success();
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function install(FluteRequest $request, string $key): Response
    {
        try {
            $mopd = $this->moduleManager->getModule($key);

            if (!$this->actions->installModule($mopd))
                return $this->error(__('def.unknown_error'));

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

        $folderName = reset($rootFolders);
        $extractPath = BASE_PATH . 'app/Modules/';

        if( $this->moduleManager->issetModule($folderName) )
            return $this->error(__('admin.modules_list.module_already_exists'));

        if (!fs()->exists($extractPath . $folderName)) {
            fs()->mkdir($extractPath . $folderName, 0755);
        }

        if (!$zip->extractTo($extractPath)) {
            $zip->close();
            fs()->remove($extractPath . $folderName); // Clean up on failure
            return $this->error(__('admin.modules_list.zip_extraction_failed'));
        }

        $zip->close();

        // Navigate into the extracted module folder
        $modulePath = $extractPath . $folderName . '/';
        $moduleJsonPath = $modulePath . 'module.json';

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

        return $this->json([
            'moduleName' => $folderName,
            'moduleVersion' => $moduleConfig['version']
        ]);
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

            return $this->success();
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}