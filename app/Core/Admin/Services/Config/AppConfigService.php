<?php

namespace Flute\Core\Admin\Services\Config;

use Flute\Core\Admin\Support\AbstractConfigService;
use Flute\Core\DiscordLink\DiscordLinkRoles;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\Response;
use WebPConvert\WebPConvert;

class AppConfigService extends AbstractConfigService
{
    public function updateConfig(array $params): Response
    {
        $config = array_merge(config('app'), [
            "name" => $params['name'] ?? config('app.name'),
            "footer_name" => $params['footer_name'] ?? config('app.footer_name', ''),
            // "footer_html" => $params['editorContent'] ?? config('app.footer_html', ''),
            "url" => $params['url'] ?? config('app.url'),
            "steam_api" => $params['steam_api'] ?? config('app.steam_api'),
            "debug" => $this->b($params['debug'] ?? config('app.debug')),
            "debug_ips" => $this->parseDebugIps($params['debugIps'] ?? implode(', ', config('app.debug_ips', []))),
            // "key" => $params['key'] ?? config('app.key'),
            "tips" => $this->b($params['tips'] ?? config('app.tips')),
            "maintenance_mode" => $params['maintenance_mode'] && $this->b($params['maintenance_mode']),
            "maintenance_message" => $params['maintenance_message'] ?? config('app.maintenance_message'),
            "discord_link_roles" => $this->b($params['discord_link_roles'] ?? config('app.discord_link_roles')),
            "timezone" => $params['timezone'] ?? config('app.timezone'),
            "notifications" => $params['notifications'] ?? config('app.notifications'),
            "notifications_new_view" => $this->b($params['notifications_new_view'] ?? config('app.notifications_new_view')),
            "mode" => $this->b($params['performanceMode'] ?? (config('app.mode') === 'performance')) ? 'performance' : 'default',
            "share" => $this->b($params['share'] ?? config('app.share')),
            "flute_copyright" => $this->b($params['flute_copyright'] ?? config('app.flute_copyright')),
            "widget_placeholders" => $this->b($params['widget_placeholders'] ?? config('app.widget_placeholders')),
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

            app(DiscordLinkRoles::class)->registerMetadata();

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
            // Validate file
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'ico'];
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/x-icon'];
            $extension = strtolower($file->getClientOriginalExtension());
            $mimeType = $file->getMimeType();

            if (!in_array($extension, $allowedExtensions) || !in_array($mimeType, $allowedMimeTypes)) {
                throw new \Exception(__('validator.invalid_file'));
            }

            $imageInfo = getimagesize($file->getPathname());
            if ($imageInfo === false) {
                throw new \Exception(__('validator.invalid_image'));
            }

            // Generate secure file name
            $fileName = hash('sha256', uniqid('', true)) . '.' . $extension;

            if ($type === 'favicon') {
                $destinationPath = public_path('assets/uploads');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $file->move($destinationPath, $fileName);
                $config[$type] = 'assets/uploads/' . $fileName;
            } else {
                $destinationPath = public_path('assets/uploads');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                if ($this->shouldConvertToWebP($file)) {
                    try {
                        $webp = $this->convertToWebP($file, $destinationPath, $fileName);
                        $config[$type] = 'assets/uploads/' . $webp;
                    } catch (\Exception $e) {
                        logs()->error($e);
                        throw new \Exception(__('validator.image_conversion_failed'));
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
