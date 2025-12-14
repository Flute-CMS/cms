<?php

namespace Flute\Core\Modules\Page\Controllers;

use Exception;
use Flute\Core\Database\Entities\PageBlock;
use Flute\Core\Modules\Page\Services\PageManager;
use Flute\Core\Modules\Page\Services\WidgetManager;
use Flute\Core\Modules\Page\Widgets\Contracts\WidgetInterface;
use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;
use Flute\Core\Validator\FluteValidator;
use InvalidArgumentException;

class WidgetController extends BaseController
{
    protected FluteValidator $validator;

    protected PageManager $pageManager;

    protected WidgetManager $widgetManager;

    /**
     * Constructor method.
     *
     * @param FluteValidator $validator     The validator service.
     * @param PageManager    $pageManager   The page manager service.
     * @param WidgetManager  $widgetManager The widget manager service.
     */
    public function __construct(
        FluteValidator $validator,
        PageManager $pageManager,
        WidgetManager $widgetManager
    ) {
        $this->validator = $validator;
        $this->pageManager = $pageManager;
        $this->widgetManager = $widgetManager;
    }

    /**
     * Retrieves the layout for a specific page path.
     *
     * @param FluteRequest $fluteRequest The incoming request containing the path.
     */
    public function getLayout(FluteRequest $fluteRequest)
    {
        $rules = [
            'path' => 'string',
        ];

        $path = $fluteRequest->query->get('path');
        if ($path === null || $path === '') {
            $path = $fluteRequest->request->get('path', '');
        }
        if ($path === null || $path === '') {
            $path = $fluteRequest->attributes->get('path', '');
        }
        if ($path === null || $path === '') {
            $referer = $fluteRequest->getReferer();
            if ($referer) {
                $pathFromReferer = parse_url($referer, PHP_URL_PATH);
                if (is_string($pathFromReferer) && $pathFromReferer !== '') {
                    $path = $pathFromReferer;
                }
            }
        }
        if ($path === null || $path === '') {
            $path = '/';
        }

        if (!$this->validator->validate(['path' => $path], $rules)) {
            $errors = collect($this->validator->getErrors()->getMessages());
            $firstError = $errors->first()[0] ?? 'Invalid input.';

            return $this->json([
                'error' => $firstError,
                'errors' => $errors->toArray(),
            ], 422);
        }

        try {
            $layout = $this->pageManager->getLayoutForPath($path);

            return $this->json([
                'layout' => $layout,
            ], 200);
        } catch (Exception $e) {
            logs()->error("Failed to retrieve layout for path {$path}: ".$e->getMessage());

            return $this->handleError($e, 'Failed to retrieve layout');
        }
    }

    /**
     * Saves the layout for a specific page path.
     *
     * @param FluteRequest $fluteRequest The incoming request containing layout data.
     */
    public function saveLayout(FluteRequest $fluteRequest)
    {
        $rules = [
            'layout' => 'array',
            'path' => 'required|string',
        ];

        if (!$this->validator->validate($fluteRequest->input(), $rules)) {
            $errors = collect($this->validator->getErrors()->getMessages());
            $firstError = $errors->first()[0] ?? 'Invalid input.';

            return $this->json([
                'error' => $firstError,
                'errors' => $errors->toArray(),
            ], 422);
        }

        $layout = $fluteRequest->input('layout', []);
        $path = $fluteRequest->input('path', '/');
        if (!is_string($path) || $path === '') {
            $path = '/';
        }
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        try {
            $this->pageManager->saveLayoutForPath($path, $layout);
            $this->toast(__('def.layout_saved'), 'success');

            return $this->json([
                'message' => __('def.layout_saved'),
            ], 200);
        } catch (Exception $e) {
            logs()->error("Failed to save layout for path {$path}: ".$e->getMessage());

            return $this->handleError($e, 'Failed to save layout');
        }
    }

    /**
     * Deletes a widget by its ID.
     *
     * @param FluteRequest $fluteRequest The incoming request.
     * @param int          $id           The ID of the widget to delete.
     */
    public function deleteWidget(FluteRequest $fluteRequest, int $id)
    {
        $widget = PageBlock::findByPK($id);

        if (!$widget) {
            $message = 'Widget not found';
            $this->toast($message, 'error');

            return $this->json(['error' => $message], 404);
        }

        try {
            $widget->delete();
            $this->toast(__('def.widget_deleted'), 'success');

            return response()->make();
        } catch (Exception $e) {
            logs('modules')->error("Failed to delete widget ID {$id}: ".$e->getMessage());
            $message = is_debug() ? $e->getMessage() : 'Failed to delete widget. Please try again later.';
            $this->toast($message, 'error');

            return $this->json([
                'error' => $message,
            ], 500);
        }
    }

    /**
     * Renders a widget based on its name and settings.
     *
     * @param FluteRequest $fluteRequest The incoming request containing widget data.
     */
    public function renderWidget(FluteRequest $fluteRequest)
    {
        $rules = [
            'widget_name' => 'required|string',
            'settings' => 'nullable',
        ];

        if (!$this->validator->validate($fluteRequest->input(), $rules)) {
            return $this->handleValidationError();
        }

        $widgetName = $fluteRequest->input('widget_name');

        try {
            $widget = $this->widgetManager->getWidget($widgetName);
            $settings = $this->resolveWidgetSettings($widget, $fluteRequest);
            $html = $widget->render($settings);

            return $this->json([
                'html' => $html,
                'settings' => $settings,
                'hasSettings' => $widget->hasSettings(),
            ]);
        } catch (InvalidArgumentException $e) {
            logs('modules')->error("Failed to render widget {$widgetName}: ".$e->getMessage());

            if (is_debug()) {
                throw $e;
            }

            return $this->json([
                'html' => '<div class="widget-error">Widget not found</div>',
            ], 404);
        } catch (Exception $e) {
            logs('modules')->error("Failed to render widget {$widgetName}: ".$e->getMessage());

            return $this->json([
                'html' => '<div class="widget-error">Failed to render widget. Please try again later.</div>',
            ], 500);
        }
    }

    /**
     * Renders multiple widgets based on their names and settings.
     *
     * @param FluteRequest $fluteRequest The incoming request containing widgets data.
     */
    public function renderWidgets(FluteRequest $fluteRequest)
    {
        $rules = [
            'widgets' => 'required|array',
            'widgets.*.widget_name' => 'required|string',
            'widgets.*.settings' => 'nullable',
        ];

        if (!$this->validator->validate($fluteRequest->input(), $rules)) {
            return $this->handleValidationError();
        }

        $widgetsData = $fluteRequest->input('widgets', []);
        $results = [];

        foreach ($widgetsData as $index => $widgetData) {
            try {
                $widgetName = $widgetData['widget_name'] ?? '';
                $widget = $this->widgetManager->getWidget($widgetName);
                $settings = $this->resolveWidgetSettings($widget, $widgetData['settings'] ?? null);
                $html = $widget->render($settings);

                $results[] = [
                    'html' => $html,
                    'settings' => $settings,
                    'hasSettings' => $widget->hasSettings(),
                ];
            } catch (InvalidArgumentException $e) {
                logs('modules')->error("Failed to render widget {$widgetName}: ".$e->getMessage());

                $errorHtml = is_debug() ?
                    '<div class="widget-error">Widget not found: ' . htmlspecialchars($e->getMessage()) . '</div>' :
                    '<div class="widget-error">Widget not found</div>';

                $results[] = [
                    'html' => $errorHtml,
                    'settings' => [],
                    'hasSettings' => false,
                ];
            } catch (Exception $e) {
                logs('modules')->error("Failed to render widget {$widgetName}: ".$e->getMessage());

                $errorHtml = is_debug() ?
                    '<div class="widget-error">Error: ' . htmlspecialchars($e->getMessage()) . '</div>' :
                    '<div class="widget-error">Failed to render widget. Please try again later.</div>';

                $results[] = [
                    'html' => $errorHtml,
                    'settings' => [],
                    'hasSettings' => false,
                ];
            }
        }

        return $this->json($results);
    }

    /**
     * Get available buttons for a widget.
     *
     * @param FluteRequest $request The incoming request.
     */
    public function getButtons(FluteRequest $request)
    {
        $rules = [
            'widget_name' => 'required|string',
        ];

        if (!$this->validator->validate($request->input(), $rules)) {
            return $this->handleValidationError();
        }

        $widgetName = $request->input('widget_name');

        try {
            $widget = $this->widgetManager->getWidget($widgetName);
            $buttons = $widget->getButtons();

            if (!$buttons) {
                return $this->json([], 200);
            }

            return $this->json($buttons, 200);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => 'Widget not found'], 404);
        } catch (Exception $e) {
            logs('modules')->error("Failed to get buttons for widget {$widgetName}: ".$e->getMessage());

            return $this->handleError($e, 'Failed to get widget buttons');
        }
    }

    /**
     * Handle widget action.
     *
     * @param FluteRequest $request The incoming request.
     */
    public function handleAction(FluteRequest $request)
    {
        $rules = [
            'widget_name' => 'required|string',
            'action' => 'required|string',
            'widgetId' => 'nullable|string',
        ];

        if (!$this->validator->validate($request->input(), $rules)) {
            $errors = collect($this->validator->getErrors()->getMessages());

            return $this->json([
                'error' => $errors->first()[0] ?? 'Invalid input',
                'errors' => $errors->toArray(),
            ], 422);
        }

        $widgetName = $request->input('widget_name');
        $action = $request->input('action');
        $widgetId = $request->input('widgetId');

        try {
            $widget = $this->widgetManager->getWidget($widgetName);
            $result = $widget->handleAction($action, $widgetId);

            return $this->json($result ?: ['success' => true]);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => 'Widget not found'], 404);
        } catch (Exception $e) {
            logs('modules')->error("Failed to handle action '{$action}' for widget {$widgetName}: ".$e->getMessage());

            return $this->handleError($e, 'Failed to handle widget action');
        }
    }

    /**
     * Displays the settings form for a specific widget.
     *
     * @param FluteRequest $request The incoming request instance.
     */
    public function settingsForm(FluteRequest $request)
    {
        $rules = [
            'widget_name' => 'required|string',
            'settings' => 'nullable|string',
        ];

        if (!$this->validator->validate($request->input(), $rules)) {
            return $this->handleValidationError();
        }

        $widgetName = $request->input('widget_name');

        try {
            $widget = $this->widgetManager->getWidget($widgetName);

            if (!$widget->hasSettings()) {
                return "<div class='widget-no-settings'>".__('def.widget_no_settings')."</div>";
            }

            $settings = json_decode($request->input('settings', '{}'), true) ?? $widget->getSettings();

            $formHtml = $widget->renderSettingsForm($settings);

            return $formHtml !== false
                ? $formHtml
                : "<div class='widget-no-settings'>".__('def.widget_no_settings')."</div>";
        } catch (Exception $e) {
            if (is_debug()) {
                throw $e;
            }
            logs('modules')->error("Failed to get settings form for widget {$widgetName}: ".$e->getMessage());

            return "<div class='widget-error'>".__('def.widget_not_found', ['name' => $widgetName])."</div>";
        }
    }

    /**
     * Saves the settings for a specific widget and returns the updated widget.
     *
     * @param FluteRequest $request The incoming request containing widget settings.
     */
    public function saveSettings(FluteRequest $request)
    {
        $rules = [
            'widget_name' => 'required|string',
        ];

        if (!$this->validator->validate($request->input(), $rules)) {
            return $this->handleValidationError();
        }

        $widgetName = $request->input('widget_name');

        try {
            $widget = $this->widgetManager->getWidget($widgetName);
            $input = $request->input();

            if (method_exists($widget, 'validateSettings')) {
                $validationResult = $widget->validateSettings($input);
                if ($validationResult !== true) {
                    $settings = $this->resolveWidgetSettings($widget, $request);
                    $formHtml = $widget->renderSettingsForm($settings);

                    return $formHtml;
                }
            }

            $savedSettings = $widget->saveSettings($input);
            $renderedHtml = $widget->render($savedSettings);

            return $this->json([
                'success' => true,
                'html' => $renderedHtml,
                'settings' => $savedSettings,
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => 'Widget not found'], 404);
        } catch (Exception $e) {
            logs('modules')->error("Failed to save settings for widget {$widgetName}: ".$e->getMessage());

            if (is_debug()) {
                throw $e;
            }

            try {
                $widget = $this->widgetManager->getWidget($widgetName);
                $settings = $this->resolveWidgetSettings($widget, $request);
                $formHtml = $widget->renderSettingsForm($settings);

                return $formHtml;
            } catch (Exception $innerE) {
                return $this->handleError($e, 'Failed to save widget settings');
            }
        }
    }

    /**
     * Get available buttons for multiple widgets.
     *
     * @param FluteRequest $request The incoming request.
     */
    public function getButtonsBatch(FluteRequest $request)
    {
        $rules = [
            'widget_names' => 'required|array',
            'widget_names.*' => 'required|string',
        ];

        if (!$this->validator->validate($request->input(), $rules)) {
            return $this->handleValidationError();
        }

        $widgetNames = $request->input('widget_names', []);
        $results = [];

        foreach ($widgetNames as $widgetName) {
            try {
                $widget = $this->widgetManager->getWidget($widgetName);
                $buttons = $widget->getButtons();
                $results[$widgetName] = $buttons ?: [];
            } catch (InvalidArgumentException $e) {
                $results[$widgetName] = [];
            } catch (Exception $e) {
                logs('modules')->error("Failed to get buttons for widget {$widgetName}: ".$e->getMessage());
                $results[$widgetName] = [];
            }
        }

        return $this->json($results);
    }

    /**
     * Handle errors in a consistent way.
     *
     * @param Exception $e              The exception instance.
     * @param string    $defaultMessage The default message to display if not in debug mode.
     */
    protected function handleError(Exception $e, string $defaultMessage = 'An error occurred')
    {
        $message = is_debug() ? $e->getMessage() : $defaultMessage.'. Please try again later.';
        $this->toast($message, 'error');

        return $this->json([
            'error' => $message,
            'debug' => is_debug() ? [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ] : null,
        ], 500);
    }

    private function handleValidationError()
    {
        $errors = collect($this->validator->getErrors()->getMessages());
        $firstError = $errors->first()[0] ?? 'Invalid input.';

        return $this->json([
            'error' => $firstError,
            'errors' => $errors->toArray(),
        ], 422);
    }

    private function resolveWidgetSettings(WidgetInterface $widget, $requestSettings): array
    {
        if (!$widget->hasSettings()) {
            return [];
        }

        // If settings is null or empty string, return default settings
        if ($requestSettings === null || $requestSettings === '') {
            return $widget->getSettings();
        }

        // If settings is a string (JSON), decode it
        if (is_string($requestSettings)) {
            $decoded = json_decode($requestSettings, true);

            return $decoded ?: $widget->getSettings();
        }

        // If settings is already an array, use it
        if (is_array($requestSettings)) {
            return $requestSettings;
        }

        // Fallback to default settings
        return $widget->getSettings();
    }
}
