<?php

namespace Flute\Admin\Packages\Backup\Controllers;

use Exception;
use Flute\Admin\Packages\Backup\Services\BackupService;
use Flute\Core\Support\BaseController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class BackupController extends BaseController
{
    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    public function download(): Response
    {
        $filename = request()->input('filename');

        if (empty($filename)) {
            return response()->json(['error' => __('admin-backup.errors.backup_not_found')], 404);
        }

        try {
            $path = $this->backupService->getBackupPath($filename);

            $response = new BinaryFileResponse($path);
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($filename));

            return $response;
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }
}
