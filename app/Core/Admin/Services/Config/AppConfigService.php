<?php

namespace Flute\Core\Admin\Services\Config;

use Flute\Core\Admin\Support\AbstractConfigService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\Response;
use WebPConvert\WebPConvert;

class AppConfigService extends AbstractConfigService
{
    public function updateConfig(array $params): Response
    {
        $config = array_merge(config('app'), [
            "name" => $params['name'],
            "url" => $params['url'],
            "steam_api" => $params['steam_api'],
            "debug" => $this->b($params['debug']),
            "debug_ips" => $this->parseDebugIps($params['debugIps'] ?? ''),
            // "key" => $params['key'],
            "tips" => $this->b($params['tips']),
            "maintenance_mode" => $this->b($params['maintenance_mode']),
            "discord_link_roles" => $this->b($params['discord_link_roles']),
            "timezone" => $params['timezone'],
            "notifications" => $params['notifications'],
            "mode" => $this->b($params['performanceMode']) === true ? 'performance' : 'default',
            "share" => $this->b($params['share']),
            "flute_copyright" => $this->b($params['flute_copyright']),
            "widget_placeholders" => $this->b($params['widget_placeholders']),
        ]);

        /** @var FileBag */
        $files = $params['files'];

        $this->processImageFile($config, $files->get('favicon'), 'favicon');
        $this->processImageFile($config, $files->get('logo'), 'logo');

        if (!isset($params['removeBg'])) {
            $this->processImageFile($config, $files->get('bg_image'), 'bg_image');
        } else {
            $this->deleteBgImage($config);
        }

        try {
            $this->fileSystemService->updateConfig($this->getConfigPath('app'), $config);

            user()->log('events.config_updated', 'app');

            return response()->success(__('def.success'));
        } catch (\Exception $e) {
            return response()->error(500, $e->getMessage());
        }
    }

    protected function deleteBgImage(&$config)
    {
        unlink(public_path($config['bg_image']));

        $config['bg_image'] = '';
    }

    protected function processImageFile(&$config, $file, $type)
    {
        if ($file instanceof UploadedFile && !$file->getError()) {
            if ($type === 'favicon') {
                $destinationPath = public_path();
                $file->move($destinationPath, 'favicon.ico');
            } else {
                $destinationPath = public_path('assets/uploads');
                $fileName = $this->generateFileName($file, $type);

                if ($this->shouldConvertToWebP($file)) {
                    try {
                        $webp = $this->convertToWebP($file, $destinationPath, $fileName);
                        $config[$type] = 'assets/uploads/' . $webp;

                    } catch (\Exception $e) {
                        //
                    }
                } else {
                    $file->move($destinationPath, $fileName);
                    $config[$type] = 'assets/uploads/' . $fileName;
                }
            }
        }
    }

    protected function generateFileName(UploadedFile $file, $type)
    {
        return hash('sha256', $type . $file->getClientOriginalName()) . '.' . $file->getClientOriginalExtension();
    }

    protected function shouldConvertToWebP(UploadedFile $file)
    {
        return in_array($file->getMimeType(), ['image/png', 'image/jpeg']) && config('profile.convert_to_webp', false);
    }

    protected function convertToWebP(UploadedFile $file, $destinationPath, $fileName)
    {
        $sourcePath = $destinationPath . '/' . $fileName;
        $file->move($destinationPath, $fileName);

        $webPFileName = pathinfo($fileName, PATHINFO_FILENAME) . '.webp';
        $webPFilePath = $destinationPath . '/' . $webPFileName;

        try {
            WebPConvert::convert($sourcePath, $webPFilePath);
            unlink($sourcePath);
            return $webPFileName;
        } catch (\Exception $e) {
            unlink($sourcePath);
            throw $e;
        }
    }

    protected function parseDebugIps(string $ips): array
    {
        return array_filter(array_map('trim', explode(',', $ips)));
    }
}
