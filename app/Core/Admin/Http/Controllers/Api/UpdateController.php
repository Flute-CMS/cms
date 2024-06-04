<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\App;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Flute\Core\Git\GitHubUpdater;
use Symfony\Component\Filesystem\Exception\IOException;

class UpdateController extends AbstractController
{
    protected $backupDir;
    protected $testUpdateDir;

    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.system');
        $this->middleware(HasPermissionMiddleware::class);

        $this->backupDir = 'storage/backup/';
        $this->testUpdateDir = path();
    }

    public function check(FluteRequest $request)
    {
        if (!admin()->update()->needUpdate()) {
            return $this->json(['need' => false]);
        }

        return $this->json([
            'need' => __('admin.update_available', [':version' => admin()->update()->latestVersion()])
        ]);
    }

    public function update(FluteRequest $request)
    {
        if (!admin()->update()->needUpdate()) {
            return $this->error(__('admin.update.no_updates'));
        }

        $updater = new GitHubUpdater('Flute-CMS', 'cms', App::VERSION, $this->testUpdateDir);

        $this->createBackup();

        $this->clearAllCache();

        try {
            $updater->update(['app', 'bootstrap', 'public', 'i18n']);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(__('admin.update.success'));
    }

    protected function createBackup()
    {
        if( !fs()->exists($this->backupDir) ) {
            fs()->mkdir($this->backupDir);
        }

        $backupPath = path($this->backupDir . App::VERSION . '-' . date('Y-m-d_H-i-s') . '.zip');
        $zip = new \ZipArchive;
        if ($zip->open($backupPath, \ZipArchive::CREATE) === TRUE) {
            $this->addFolderToZip(path('app'), $zip, 'app');
            $this->addFolderToZip(path('bootstrap'), $zip, 'bootstrap');
            $this->addFolderToZip(path('public'), $zip, 'public');
            $this->addFolderToZip(path('i18n'), $zip, 'i18n');
            $zip->close();
        } else {
            throw new \Exception("Could not create backup ZIP file at: $backupPath");
        }
    }

    protected function addFolderToZip($source, &$zip, $folderName)
    {
        if (extension_loaded('zip')) {
            if (file_exists($source)) {
                $source = realpath($source);
                if (is_dir($source)) {
                    $files = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
                        \RecursiveIteratorIterator::SELF_FIRST
                    );
                    foreach ($files as $file) {
                        $file = realpath($file);
                        $relativePath = $folderName . '/' . substr($file, strlen($source) + 1);
                        if (is_dir($file)) {
                            $zip->addEmptyDir($relativePath);
                        } else if (is_file($file)) {
                            $zip->addFile($file, $relativePath);
                        }
                    }
                } else if (is_file($source)) {
                    $zip->addFile($source, $folderName . '/' . basename($source));
                }
            }
        } else {
            throw new \Exception("ZIP extension is not loaded.");
        }
    }

    protected function clearAllCache()
    {
        $proxiesPath = BASE_PATH . '/storage/app/proxies/*';
        $viewsPath = BASE_PATH . '/storage/app/views/*';
        $translationsPath = BASE_PATH . '/storage/app/translations/*';
        $cssCachePath = BASE_PATH . '/public/assets/css/cache/*';
        $jsCachePath = BASE_PATH . '/public/assets/js/cache/*';

        $this->deleteFs($proxiesPath);
        $this->deleteFs($viewsPath);
        $this->deleteFs($translationsPath);
        $this->deleteFs($cssCachePath);
        $this->deleteFs($jsCachePath);

        cache()->clear();
    }

    protected function deleteFs($path)
    {
        $filesystem = fs();

        try {
            $filesystem->remove(glob($path));
        } catch (IOException $exception) {
            logs()->error($exception);
        }
    }
}
