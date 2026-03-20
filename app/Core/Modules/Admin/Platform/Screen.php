<?php

namespace Flute\Admin\Platform;

use Clickfwd\Yoyo\Exceptions\BypassRenderMethod;
use Clickfwd\Yoyo\YoyoHelpers;
use Exception;
use Flute\Admin\Platform\Contracts\ScreenInterface;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Layouts\Table;
use Flute\Admin\Services\TableExportService;
use Flute\Core\Contracts\FluteComponentInterface;
use Flute\Core\Support\FluteComponent;
use Illuminate\Support\Arr;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Response;
use TypeError;

/**
 * Abstract class Screen.
 *
 * Base class for creating screens in the admin panel.
 */
abstract class Screen extends FluteComponent implements ScreenInterface
{
    public $modalId = null;

    public $slug = null;

    public $modalParams = null;

    public $js = [];

    public $css = [];

    protected $additionalLayouts;

    /**
     * Boot the screen component.
     *
     * Unlike simple Yoyo components that restore state from $_REQUEST,
     * Screens fully reinitialize their state in mount() on every request.
     * We only assign from template $variables (e.g. 'slug') and handle
     * modal state from the request explicitly.
     */
    public function boot(array $variables, array $attributes): FluteComponentInterface
    {
        if (!is_array($variables)) {
            $variables = [];
        }

        $this->variables = $variables;
        $this->attributes = $attributes;
        $this->validator = validator();

        foreach ($variables as $key => $value) {
            if (property_exists($this, $key)) {
                try {
                    $this->{$key} = $value;
                } catch (TypeError $e) {
                    continue;
                }
            }
        }

        $modalId = $this->request->get('modalId');
        if ($modalId !== null && $this->isAllowedModalMethod($modalId)) {
            $this->modalId = $modalId;
        }

        $modalParams = $this->request->get('modalParams');
        if ($modalParams !== null && $modalParams !== 'null') {
            $this->modalParams = collect(is_string($modalParams) ? encrypt()->decrypt($modalParams) : $modalParams);
        } else {
            $this->modalParams = null;
        }

        // Check access before any action method is dispatched by ComponentManager.
        // This prevents unauthorized users from executing mutation actions (e.g. delete)
        // even though render() would later redirect to 403.
        $action = $this->request->get('component', '');
        $actionName = explode('/', $action)[1] ?? 'render';

        if (!in_array($actionName, ['render', 'refresh'], true) && !$this->checkAccess()) {
            $this->response->status(Response::HTTP_FORBIDDEN);
            $this->omitResponse = true;
        }

        return $this;
    }

    /**
     * Loads a JavaScript file.
     *
     * @param string $js The path to the JavaScript file.
     */
    public function loadJS(string $js)
    {
        $this->js[] = path($js);
    }

    /**
     * Loads a CSS file.
     *
     * @param string $css The path to the CSS file.
     */
    public function loadCSS(string $css)
    {
        $this->css[] = path($css);
    }

    /**
     * Defines the base view for the screen.
     */
    public function screenBaseView(): string
    {
        return 'admin::layouts.base';
    }

    /**
     * The name of the screen to be displayed in the header.
     */
    public function name(): ?string
    {
        return $this->name ?? null;
    }

    /**
     * Get the JavaScript files to be loaded.
     */
    public function getJs(): array
    {
        return $this->js;
    }

    /**
     * Get the CSS files to be loaded.
     */
    public function getCss(): array
    {
        return $this->css;
    }

    /**
     * A description of the screen to be displayed in the header.
     */
    public function description(): ?string
    {
        return $this->description ?? null;
    }

    /**
     * The popover text for the screen to be displayed in the header.
     */
    public function popover(): ?string
    {
        return $this->get('popover');
    }

    /**
     * The permissions required to access this screen.
     */
    public function permission(): ?iterable
    {
        return isset($this->permission) ? Arr::wrap($this->permission) : null;
    }

    /**
     * The command buttons for this screen.
     *
     * @return \Flute\Admin\Platform\Contracts\Actionable[]
     */
    public function commandBar()
    {
        return [];
    }

    /**
     * Define the layouts for the screen.
     */
    abstract public function layout(): array;

    /**
     * Builds the screen using the given data repository.
     *
     * @return mixed
     */
    public function build(Repository $repository)
    {
        $repository->set('js', $this->getJs());

        return LayoutFactory::blank([
            $this->layout(),
            $this->additionalLayouts,
        ])->build($repository);
    }

    /**
     * Handles the incoming request.
     */
    public function render()
    {
        if ($this->omitResponse) {
            throw new BypassRenderMethod($this->response->getStatusCode());
        }

        $repository = new Repository($this->viewVars());

        $repository->set('slug', $this->slug);

        if (!$this->checkAccess()) {
            return $this->redirect('admin/403');
        }

        $exportFormat = request()->input('export');
        if ($exportFormat && in_array($exportFormat, ['csv', 'excel'])) {
            $this->handleExport($repository, $exportFormat);
            $this->skipRenderWithStatus(200);

            throw new BypassRenderMethod(200);
        }

        if ($this->modalId !== null) {
            if ($this->modalParams !== null) {
                $repository->set('modalParams', $this->modalParams);

                foreach ($this->modalParams as $key => $val) {
                    $repository->set($key, $val);
                }
            }

            $repository->set('modalId', $this->modalId);

            $modal = $this->{$this->modalId}($repository);

            if ($modal) {
                $this->additionalLayouts[] = $modal;
            }
        }

        return $this->view($this->screenBaseView(), [
            'screenName' => $this->name(),
            'screenDescription' => $this->description(),
            'screenPopover' => $this->popover(),
            'screenCommandBar' => $this->commandBar(),
            'screenLayouts' => $this->build($repository),
            'js' => $this->getJs(),
            'css' => $this->getCss(),
        ]);
    }

    public function openModal(string $modalFunc, $params = null)
    {
        if (!$this->isAllowedModalMethod($modalFunc)) {
            throw new Exception('Modal ' . $modalFunc . ' is not a valid modal method on this screen');
        }

        $decryptedParams = is_string($params) ? encrypt()->decrypt($params) : $params;

        $this->modalId = $modalFunc;
        $this->modalParams = collect($decryptedParams);
    }

    public function closeModal()
    {
        $this->modalId = null;
        $this->modalParams = null;
    }

    public function get($property, $default = null)
    {
        $studlyProperty = YoyoHelpers::studly($property);

        if (method_exists($this, $computedMethodName = 'get' . $studlyProperty . 'Property')) {
            if (isset($this->computedPropertyCache[$property])) {
                return $this->computedPropertyCache[$property];
            }

            return $this->computedPropertyCache[$property] = $this->$computedMethodName();
        }

        return $default;
    }

    /**
     * Check if a method name is allowed as a modal handler.
     * Only methods declared on the concrete Screen subclass (not inherited) are allowed.
     */
    protected function isAllowedModalMethod(string $methodName): bool
    {
        if (!is_callable([$this, $methodName])) {
            return false;
        }

        try {
            $ref = new ReflectionMethod($this, $methodName);

            // Only allow methods declared on the concrete subclass, not inherited ones
            return $ref->getDeclaringClass()->getName() === static::class;
        } catch (ReflectionException $e) {
            return false;
        }
    }

    // clear opcache & jit cache
    protected function clearOpcache()
    {
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    /**
     * Handle table export request.
     */
    protected function handleExport(Repository $repository, string $format): void
    {
        $tableId = request()->input('table', 'default');
        $table = $this->findExportableTable($tableId);

        if (!$table || !$table->isExportable()) {
            return;
        }

        $exportData = $table->getExportData($repository);
        $filename = $table->getExportFilename() . '_' . date('Y-m-d_H-i-s');

        $exportService = new TableExportService();

        if ($format === 'csv') {
            $exportService->exportCsv($exportData['rows'], $exportData['columns'], $filename . '.csv');
        } else {
            $exportService->exportExcel($exportData['rows'], $exportData['columns'], $filename . '.xlsx');
        }
    }

    /**
     * Find an exportable table layout by ID.
     */
    protected function findExportableTable(string $tableId): ?Table
    {
        $layouts = $this->layout();

        return $this->searchTableInLayouts($layouts, $tableId);
    }

    /**
     * Recursively search for a table layout.
     */
    protected function searchTableInLayouts($layouts, string $tableId): ?Table
    {
        if (!is_array($layouts)) {
            $layouts = [$layouts];
        }

        foreach ($layouts as $layout) {
            if ($layout instanceof Table) {
                // Check if this is the table we're looking for
                $target = $this->getTableTarget($layout);
                if ($target === $tableId || $tableId === 'default') {
                    return $layout;
                }
            }

            // Check nested layouts
            if (is_object($layout) && method_exists($layout, 'getLayouts')) {
                $result = $this->searchTableInLayouts($layout->getLayouts(), $tableId);
                if ($result !== null) {
                    return $result;
                }
            }

            if (is_array($layout)) {
                $result = $this->searchTableInLayouts($layout, $tableId);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
    }

    /**
     * Get the target property from a table layout.
     */
    protected function getTableTarget(Table $table): string
    {
        return $table->getTarget();
    }

    protected function checkAccess(): bool
    {
        if (!user()->isLoggedIn()) {
            return false; // User is not authenticated
        }

        if ($this->permission() === null) {
            return true; // No permissions required
        }

        return user()->can($this->permission());
    }
}
