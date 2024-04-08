<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Doctrine\Common\Collections\Criteria;
use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\Theme;
use Flute\Core\Database\Entities\ThemeSettings;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Flute\Core\Theme\ThemeActions;
use Flute\Core\Theme\ThemeManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

class ThemesController extends AbstractController
{
    protected ThemeManager $themeManager;
    protected ThemeActions $actions;

    public function __construct(ThemeManager $themeManager, ThemeActions $themeActions)
    {
        HasPermissionMiddleware::permission('admin.templates');
        $this->middleware(HasPermissionMiddleware::class);

        $this->themeManager = $themeManager;
        $this->actions = $themeActions;
    }

    public function changeSettings(FluteRequest $request, string $key)
    {
        $theme = $this->themeManager->getTheme($key);
        $settings = $request->input('settings');

        if (!$theme) {
            return $this->error(__('admin.themes_list.unknown_theme'));
        }

        foreach ($theme->getSettings() as $key => $setting) {
            $setting->value = $settings[$setting->key] ?? $setting->value;
        }

        user()->log('events.theme_settings_changed', $key);

        transaction($theme)->run();

        return $this->success();
    }

    public function enable(FluteRequest $request, string $key): Response
    {
        try {
            $this->actions->activateTheme($key);

            user()->log('events.theme_enabled', $key);

            return $this->success();
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function disable(FluteRequest $request, string $key): Response
    {
        try {
            $this->actions->disableTheme($key);
            user()->log('events.theme_disabled', $key);

            return $this->success();
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function install(FluteRequest $request, string $key): Response
    {
        try {
            $this->actions->installTheme($key);
            user()->log('events.theme_installed', $key);

            return $this->success();
        } catch (\Exception $e) {
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
            return $this->error(__('admin.themes_list.upload_zip_required'));
        }

        if ($file->getClientMimeType() !== 'application/zip' && $file->getClientMimeType() !== 'application/x-zip-compressed') {
            return $this->error(__('admin.themes_list.invalid_zip'));
        }

        // $maxSize = 10 * 1000000;
        // if ($file->getSize() > $maxSize) {
        //     return $this->error(__('admin.themes_list.max_zip_size', ['%d' => $maxSize]));
        // }

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
            return $this->error(__('admin.themes_list.zip_open_failed'));
        }

        $rootFolders = $this->getRootFolders($zip);
        if (count($rootFolders) !== 1) {
            $zip->close();
            return $this->error(__('admin.themes_list.single_folder_expected'));
        }

        $folderName = reset($rootFolders);
        $extractPath = BASE_PATH . 'app/Themes/';

        try {
            if ($this->themeManager->getTheme($folderName))
                return $this->error(__('admin.themes_list.theme_already_exists'));
        } catch (\Exception $e) {
            // 
        }

        if (!fs()->exists($extractPath . $folderName)) {
            fs()->mkdir($extractPath . $folderName, 0755);
        }

        if (!$zip->extractTo($extractPath)) {
            $zip->close();
            fs()->remove($extractPath . $folderName); // Clean up on failure
            return $this->error(__('admin.themes_list.zip_extraction_failed'));
        }

        $zip->close();

        $themePath = $extractPath . $folderName . '/';
        $themeLoaderPath = $themePath . 'ThemeLoader.php';

        if (!fs()->exists($themeLoaderPath)) {
            fs()->remove($themePath);
            return $this->error(__('admin.themes_list.loader_not_found'));
        }

        user()->log('events.theme_uploaded', $folderName);

        return $this->json([
            'themeName' => $folderName,
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
            $this->actions->uninstallTheme($key);
            user()->log('events.theme_deleted', $key);

            return $this->success();
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}