<?php

namespace Flute\Core\Modules\Page\Controllers;

use Exception;
use Flute\Core\Support\BaseController;
use Flute\Core\Support\FileUploader;
use Flute\Core\Support\FluteRequest;
use Flute\Core\Theme\ThemeActions;
use Flute\Core\Validator\FluteValidator;
use Nette\Utils\Json;

class ColorController extends BaseController
{
    protected ThemeActions $themeActions;

    protected FluteValidator $validator;

    /**
     * Constructor method.
     *
     * @param ThemeActions   $themeActions The theme actions service.
     * @param FluteValidator $validator     The validator service.
     */
    public function __construct(ThemeActions $themeActions, FluteValidator $validator)
    {
        $this->themeActions = $themeActions;
        $this->validator = $validator;
    }

    /**
     * Saves the color settings for a theme.
     *
     * @param FluteRequest $fluteRequest The incoming request containing color data.
     */
    public function saveColors(FluteRequest $fluteRequest)
    {
        $colors = Json::decode($fluteRequest->input('colors', '{}'), true);
        $theme = $fluteRequest->input('theme', 'dark');
        $containerWidth = $fluteRequest->input('containerWidth', 'container');

        $rules = [
            '--accent' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            '--primary' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            '--secondary' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            '--background' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            '--text' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            '--border1' => 'required|numeric|min:0.25|max:4',
            '--background-type' => 'sometimes|string|in:solid,linear-gradient,radial-gradient,mesh-gradient,subtle-gradient,aurora-gradient,sunset-gradient,ocean-gradient,spotlight-gradient',
            '--bg-grad1' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            '--bg-grad2' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            '--bg-grad3' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'theme' => 'required|string|in:dark,light',
            'containerWidth' => 'sometimes|string|in:container,fullwidth',
        ];

        $data = array_merge($colors, ['theme' => $theme, 'containerWidth' => $containerWidth]);

        if (!$this->validator->validate($data, $rules)) {
            $errors = collect($this->validator->getErrors()->getMessages());
            $firstError = $errors->first()[0] ?? 'Invalid input.';
            $this->toast($firstError, 'error');

            return $this->json([
                'errors' => $errors->toArray(),
            ], 422);
        }

        try {
            if (isset($colors['--border1'])) {
                $colors['--border1'] = $colors['--border1'] . 'rem';
                $colors['--border05'] = (floatval($colors['--border1']) / 2) . 'rem';
            }

            // Set default background type if not provided
            if (!isset($colors['--background-type'])) {
                $colors['--background-type'] = 'solid';
            }

            // Add container width to colors array
            $colors['--container-width'] = $containerWidth;

            $currentTheme = app('flute.view.manager')->getCurrentTheme();
            $this->themeActions->updateThemeColors($currentTheme, $colors, $theme);
            $this->toast(__('def.colors_updated'), 'success');

            return $this->json([
                'message' => __('def.colors_updated'),
            ], 200);
        } catch (Exception $e) {
            logs('templates')->error("Failed to update theme colors: " . $e->getMessage());
            $message = is_debug() ? $e->getMessage() : 'Failed to update theme colors. Please try again later.';
            $this->toast($message, 'error');

            return $this->json([
                'error' => $message,
            ], 500);
        }
    }

    /**
     * Saves the customization settings for a theme.
     *
     * @param FluteRequest $fluteRequest The incoming request containing customization data.
     */
    public function saveCustomize(FluteRequest $fluteRequest)
    {
        $settings = $fluteRequest->input('settings', []);
        $theme = $fluteRequest->input('theme', 'dark');

        if (is_string($settings)) {
            $settings = Json::decode($settings, true);
        }

        $rules = [
            'nav_type' => 'sometimes|string|in:horizontal,sidebar,sidebar-mini',
            'nav_position' => 'sometimes|string|in:top,sticky,fixed',
            'nav_blur' => 'sometimes|boolean',
            'footer_type' => 'sometimes|string|in:default,minimal,expanded,hidden',
            'footer_columns' => 'sometimes|integer|min:1|max:5',
            'footer_socials' => 'sometimes|boolean',
            'font_family' => 'sometimes|string|max:50',
            'heading_font' => 'sometimes|string|max:50',
            'font_size' => 'sometimes|integer|min:12|max:24',
            'line_height' => 'sometimes|numeric|min:1|max:3',
            'heading_weight' => 'sometimes|integer|in:400,500,600,700,800',
            'container_padding' => 'sometimes|integer|min:0|max:64',
            'section_gap' => 'sometimes|integer|min:0|max:96',
            'card_padding' => 'sometimes|integer|min:0|max:48',
            'element_gap' => 'sometimes|integer|min:0|max:32',
            'shadow_intensity' => 'sometimes|integer|min:0|max:100',
            'blur_amount' => 'sometimes|integer|min:0|max:30',
            'animations' => 'sometimes|boolean',
            'transition_speed' => 'sometimes|string|in:0.1s,0.2s,0.3s,0.5s',
            'hover_scale' => 'sometimes|boolean',
            'max_width' => 'sometimes|integer|min:800|max:2560',
            'sidebar_width' => 'sometimes|integer|min:180|max:400',
            'content_align' => 'sometimes|string|in:left,center',
            'theme' => 'required|string|in:dark,light',
        ];

        $data = array_merge($settings, ['theme' => $theme]);

        if (!$this->validator->validate($data, $rules)) {
            $errors = collect($this->validator->getErrors()->getMessages());
            $firstError = $errors->first()[0] ?? 'Invalid input.';
            $this->toast($firstError, 'error');

            return $this->json([
                'success' => false,
                'errors' => $errors->toArray(),
            ], 422);
        }

        try {
            $customizeSettings = [
                '--nav-type' => $settings['nav_type'] ?? 'horizontal',
                '--nav-position' => $settings['nav_position'] ?? 'top',
                '--nav-blur' => ($settings['nav_blur'] ?? true) ? 'true' : 'false',
                '--footer-type' => $settings['footer_type'] ?? 'default',
                '--footer-columns' => (string) ($settings['footer_columns'] ?? 3),
                '--footer-socials' => ($settings['footer_socials'] ?? true) ? 'true' : 'false',
                '--font' => $settings['font_family'] ?? 'Manrope',
                '--font-header' => $settings['heading_font'] ?? 'inherit',
                '--base-font-size' => ($settings['font_size'] ?? 16) . 'px',
                '--line-height' => (string) ($settings['line_height'] ?? 1.5),
                '--heading-weight' => (string) ($settings['heading_weight'] ?? 600),
                '--container-padding' => ($settings['container_padding'] ?? 16) . 'px',
                '--section-gap' => ($settings['section_gap'] ?? 24) . 'px',
                '--card-padding' => ($settings['card_padding'] ?? 16) . 'px',
                '--element-gap' => ($settings['element_gap'] ?? 12) . 'px',
                '--shadow-intensity' => (string) (($settings['shadow_intensity'] ?? 30) / 100),
                '--blur-amount' => ($settings['blur_amount'] ?? 10) . 'px',
                '--animations' => ($settings['animations'] ?? true) ? 'true' : 'false',
                '--transition' => $settings['transition_speed'] ?? '0.2s',
                '--hover-scale' => ($settings['hover_scale'] ?? true) ? 'true' : 'false',
                '--max-content-width' => ($settings['max_width'] ?? 1200) . 'px',
                '--sidebar-width' => ($settings['sidebar_width'] ?? 260) . 'px',
                '--content-align' => $settings['content_align'] ?? 'left',
            ];

            $currentTheme = app('flute.view.manager')->getCurrentTheme();
            $this->themeActions->updateThemeColors($currentTheme, $customizeSettings, $theme);

            $this->toast(__('def.settings_updated'), 'success');

            return $this->json([
                'success' => true,
                'message' => __('def.settings_updated'),
            ], 200);
        } catch (Exception $e) {
            logs('templates')->error("Failed to update theme customization: " . $e->getMessage());
            $message = is_debug() ? $e->getMessage() : 'Failed to update theme customization. Please try again later.';
            $this->toast($message, 'error');

            return $this->json([
                'success' => false,
                'error' => $message,
            ], 500);
        }
    }

    /**
     * Saves all theme settings (colors + customization) in one request.
     *
     * @param FluteRequest $fluteRequest The incoming request containing all theme data.
     */
    public function saveTheme(FluteRequest $fluteRequest)
    {
        $colors = $fluteRequest->input('colors', []);
        $settings = $fluteRequest->input('settings', []);
        $theme = $fluteRequest->input('theme', 'dark');

        if (is_string($colors)) {
            $colors = Json::decode($colors, true);
        }
        if (is_string($settings)) {
            $settings = Json::decode($settings, true);
        }

        $colorRules = [
            '--accent' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            '--primary' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            '--secondary' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            '--background' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            '--text' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            '--border1' => 'sometimes|numeric|min:0|max:4',
            '--border05' => 'sometimes|numeric|min:0|max:2',
            '--background-type' => 'sometimes|string|in:solid,linear-gradient,radial-gradient,mesh-gradient,subtle-gradient,aurora-gradient,sunset-gradient,ocean-gradient,spotlight-gradient',
            '--bg-grad1' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            '--bg-grad2' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            '--bg-grad3' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            '--container-width' => 'sometimes|string|in:container,fullwidth',
            '--nav-style' => 'sometimes|string|in:default,pill,pill-transparent,pill-full,sidebar',
            '--sidebar-style' => 'sometimes|string|in:default,mini',
            '--sidebar-mode' => 'sometimes|string|in:full,minimal',
            '--sidebar-position' => 'sometimes|string|in:top,center',
            '--footer-type' => 'sometimes|string|in:default,minimal,expanded,glass,centered,hidden',
            '--footer-socials' => 'sometimes|string|in:true,false',
            '--design-preset' => 'sometimes|string|in:default,vapor,neon,brutalist,obsidian,golden,coral,matrix,arctic,rose',
        ];

        // Validate settings (fonts, spacing, effects)
        $settingsRules = [
            'font' => 'sometimes|string|max-str-len:100',
            'font_header' => 'sometimes|string|max-str-len:100',
            'font_scale' => 'sometimes|numeric|min:1|max:1.5',
            'space_xs' => 'sometimes|string|max:20',
            'space_sm' => 'sometimes|string|max:20',
            'space_md' => 'sometimes|string|max:20',
            'space_lg' => 'sometimes|string|max:20',
            'space_xl' => 'sometimes|string|max:20',
            'transition' => 'sometimes|string|max:20',
            'blur_amount' => 'sometimes|string|max:20',
            'card_opacity' => 'sometimes|string|max:20',
            'glow_intensity' => 'sometimes|string|max:20',
            'max_content_width' => 'sometimes|string|max:20',
            'widget_gap' => 'sometimes|string|max:20',
            'shadow_small' => 'sometimes|string|max-str-len:500',
            'shadow_medium' => 'sometimes|string|max-str-len:500',
            'shadow_large' => 'sometimes|string|max-str-len:500',
        ];

        $allRules = array_merge($colorRules, $settingsRules, ['theme' => 'required|string|in:dark,light']);
        $data = array_merge($colors, $settings, ['theme' => $theme]);

        if (!$this->validator->validate($data, $allRules)) {
            $errors = collect($this->validator->getErrors()->getMessages());
            $firstError = $errors->first()[0] ?? 'Invalid input.';
            $this->toast($firstError, 'error');

            return $this->json([
                'success' => false,
                'errors' => $errors->toArray(),
            ], 422);
        }

        try {
            $themeSettings = [];

            foreach ($colors as $key => $value) {
                if (!str_starts_with($key, '--')) {
                    continue;
                }

                if ($key === '--border1' && is_numeric($value)) {
                    $themeSettings[$key] = $value . 'rem';
                    $themeSettings['--border05'] = (floatval($value) / 2) . 'rem';
                } elseif ($key === '--border05') {
                    continue;
                } else {
                    $themeSettings[$key] = $value;
                }
            }

            $settingsMap = [
                'font' => '--font',
                'font_header' => '--font-header',
                'font_scale' => '--font-scale',
                'space_xs' => '--space-xs',
                'space_sm' => '--space-sm',
                'space_md' => '--space-md',
                'space_lg' => '--space-lg',
                'space_xl' => '--space-xl',
                'transition' => '--transition',
                'blur_amount' => '--blur-amount',
                'card_opacity' => '--card-opacity',
                'glow_intensity' => '--glow-intensity',
                'max_content_width' => '--max-content-width',
                'widget_gap' => '--widget-gap',
                'shadow_small' => '--shadow-small',
                'shadow_medium' => '--shadow-medium',
                'shadow_large' => '--shadow-large',
            ];

            foreach ($settings as $key => $value) {
                $cssKey = $settingsMap[$key] ?? null;
                if ($cssKey && $value !== null && $value !== '') {
                    $themeSettings[$cssKey] = str_replace("'", '', $value);
                }
            }

            $currentTheme = app('flute.view.manager')->getCurrentTheme();
            $this->themeActions->updateThemeColors($currentTheme, $themeSettings, $theme);

            $this->toast(__('page-edit.settings_saved'), 'success');

            return $this->json([
                'success' => true,
                'message' => __('page-edit.settings_saved'),
            ], 200);
        } catch (Exception $e) {
            logs('templates')->error("Failed to save theme settings: " . $e->getMessage());
            $message = is_debug() ? $e->getMessage() : 'Failed to save theme settings. Please try again later.';
            $this->toast($message, 'error');

            return $this->json([
                'success' => false,
                'error' => $message,
            ], 500);
        }
    }

    /**
     * Uploads a site image (background or logo) and saves to config.
     *
     * @param FluteRequest $fluteRequest The incoming request with image file and type.
     */
    public function uploadSiteImage(FluteRequest $fluteRequest)
    {
        $allowedTypes = ['bg_image', 'bg_image_light', 'logo', 'logo_light'];
        $type = $fluteRequest->input('type');

        if (!in_array($type, $allowedTypes, true)) {
            return $this->json(['error' => 'Invalid image type.'], 422);
        }

        $file = $fluteRequest->files->get('image');

        if (!$file || !$file->isValid()) {
            return $this->json(['error' => 'No valid file uploaded.'], 422);
        }

        try {
            /** @var FileUploader $uploader */
            $uploader = app(FileUploader::class);
            $path = $uploader->uploadImage($file, 10);

            config()->set("app.{$type}", $path);
            config()->save();

            $this->toast(__('page-edit.upload_success'), 'success');

            return $this->json([
                'success' => true,
                'url' => asset($path),
                'path' => $path,
            ]);
        } catch (Exception $e) {
            logs('templates')->error("Failed to upload site image ({$type}): " . $e->getMessage());
            $message = is_debug() ? $e->getMessage() : __('page-edit.upload_error');

            return $this->json(['error' => $message], 500);
        }
    }

    /**
     * Deletes (clears) a site image from config.
     *
     * @param FluteRequest $fluteRequest The incoming request with image type.
     */
    public function deleteSiteImage(FluteRequest $fluteRequest)
    {
        $allowedTypes = ['bg_image', 'bg_image_light', 'logo', 'logo_light'];
        $type = $fluteRequest->input('type');

        if (!in_array($type, $allowedTypes, true)) {
            return $this->json(['error' => 'Invalid image type.'], 422);
        }

        try {
            $defaults = [
                'logo' => 'assets/img/logo.svg',
                'logo_light' => 'assets/img/logo-light.svg',
                'bg_image' => '',
                'bg_image_light' => '',
            ];

            config()->set("app.{$type}", $defaults[$type]);
            config()->save();

            $this->toast(__('page-edit.delete_success'), 'success');

            return $this->json([
                'success' => true,
                'default' => $defaults[$type],
            ]);
        } catch (Exception $e) {
            logs('templates')->error("Failed to delete site image ({$type}): " . $e->getMessage());

            return $this->json(['error' => 'Failed to delete image.'], 500);
        }
    }
}
