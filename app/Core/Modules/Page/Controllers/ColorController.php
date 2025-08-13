<?php

namespace Flute\Core\Modules\Page\Controllers;

use Exception;
use Flute\Core\Support\BaseController;
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
}
