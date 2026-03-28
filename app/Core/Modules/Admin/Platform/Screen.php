<?php

namespace Flute\Admin\Platform;

use Clickfwd\Yoyo\ClassHelpers;
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
    /**
     * Properties that must NEVER be restored from request data.
     * These control access, rendering, and security — overwriting them
     * via a crafted POST would bypass permission checks or break the screen.
     *
     * Subclasses can extend this list by overriding protectedProperties().
     */
    private const PROTECTED_PROPERTIES = [
        'name',
        'description',
        'permission',
        'modalId',
        'modalParams',
        'slug',
        'js',
        'css',
        'isEditMode',
    ];

    public $modalId = null;

    public $slug = null;

    public $modalParams = null;

    public $js = [];

    public $css = [];

    /**
     * Set to true when HMAC verification fails — signals mount()
     * to abort with an error instead of proceeding.
     */
    protected bool $stateTampered = false;

    protected $additionalLayouts;

    /**
     * Boot the screen component.
     *
     * Restores public properties from Yoyo request data (action POST requests)
     * so that identifiers like $userId are available in mount() even when
     * the request URL is /admin/live (no route params).
     *
     * SECURITY: Properties listed in PROTECTED_PROPERTIES / protectedProperties()
     * are never overwritten from request data to prevent privilege escalation.
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

        // Restore public properties from Yoyo POST data, excluding protected ones.
        $requestData = $this->request->all();
        if (is_array($requestData)) {
            $protected = array_flip($this->protectedProperties());
            foreach (ClassHelpers::getPublicProperties($this, Screen::class) as $property) {
                if (
                    isset($protected[$property])
                    || array_key_exists($property, $variables)
                    || !array_key_exists($property, $requestData)
                ) {
                    continue;
                }

                try {
                    $this->{$property} = $requestData[$property];
                } catch (TypeError $e) {
                    continue;
                }
            }

            // Verify HMAC of signed properties to detect tampering.
            // If HMAC is missing or invalid, reset signed properties to defaults.
            // An attacker cannot bypass by omitting _stateHmac — we require it
            // whenever signed properties are present in the POST body.
            $signedProps = $this->resolveSignedProperties();
            if (!empty($signedProps)) {
                $hasSignedInPost = false;
                foreach ($signedProps as $prop) {
                    if (array_key_exists($prop, $requestData)) {
                        $hasSignedInPost = true;
                        break;
                    }
                }

                $postedHmac = $requestData['_stateHmac'] ?? null;

                if ($hasSignedInPost && ( $postedHmac === null || !$this->verifyStateHmac($postedHmac) )) {
                    foreach ($signedProps as $prop) {
                        if (property_exists($this, $prop)) {
                            $this->{$prop} = null;
                        }
                    }

                    logs()->warning(
                        'Screen state HMAC ' . ( $postedHmac === null ? 'missing' : 'verification failed' ),
                        [
                            'screen' => static::class,
                            'ip' => request()->getClientIp(),
                        ],
                    );

                    $this->stateTampered = true;
                }
            }
        }

        $modalId = $this->request->get('modalId');
        if ($modalId !== null && $this->isAllowedModalMethod($modalId)) {
            $this->modalId = $modalId;
        }

        $modalParams = $this->request->get('modalParams');
        if ($modalParams !== null && $modalParams !== 'null' && is_string($modalParams)) {
            try {
                $this->modalParams = collect(encrypt()->decrypt($modalParams));
            } catch (\Throwable $e) {
                $this->modalParams = collect();
            }
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

        // Compute HMAC and collect signed property values for hidden inputs in base.blade.php
        $signedProps = $this->resolveSignedProperties();
        $signedValues = [];
        foreach ($signedProps as $prop) {
            if (property_exists($this, $prop)) {
                $signedValues[$prop] = $this->{$prop};
            }
        }
        $stateHmac = !empty($signedProps) ? $this->buildHmac($signedProps) : null;

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
            'screenStateHmac' => $stateHmac,
            'screenSignedValues' => $signedValues,
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

    /**
     * Redirect for internal CMS URLs via Yoyo.
     *
     * JS interceptor in script.js converts Yoyo-Redirect into htmx.ajax()
     * for admin URLs, preserving sidebar and showing toasts.
     * Toasts are added to X-Toasts header automatically by ToastResponseListener.
     */
    protected function boostRedirect(string $url): void
    {
        $this->redirect($url);
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

    /**
     * Returns the list of public property names that must never be
     * overwritten from request data. Override in subclasses to extend.
     *
     * @return string[]
     */
    protected function protectedProperties(): array
    {
        return self::PROTECTED_PROPERTIES;
    }

    /**
     * Extra properties to HMAC-sign (on top of auto-detected ones).
     * Override in subclasses for non-standard naming.
     *
     * @return string[]
     */
    protected function signedProperties(): array
    {
        return [];
    }

    /**
     * Properties to EXCLUDE from auto-detection signing.
     * Override in subclasses if a property like `$categoryId` is
     * user-editable (e.g. a select dropdown) and should not be signed.
     *
     * @return string[]
     */
    protected function unsignedProperties(): array
    {
        return [];
    }

    /**
     * Collect all properties that must be HMAC-signed.
     *
     * Auto-detects public int/nullable-int properties whose name matches
     * the pattern `id` or `*Id` (e.g. userId, serverId, pageId).
     * Merges with explicit signedProperties() and removes unsignedProperties().
     *
     * @return string[]
     */
    private function resolveSignedProperties(): array
    {
        $protected = array_flip($this->protectedProperties());
        $unsigned = array_flip($this->unsignedProperties());
        $signed = [];

        // Auto-detect: public properties named "id" or ending with "Id", typed as int
        $ref = new \ReflectionClass($this);
        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->getDeclaringClass()->getName() === self::class) {
                continue; // skip Screen's own properties
            }

            $name = $prop->getName();
            if (isset($protected[$name]) || isset($unsigned[$name])) {
                continue;
            }

            if ($name === 'id' || str_ends_with($name, 'Id')) {
                $type = $prop->getType();
                if (
                    $type === null // untyped — sign conservatively
                    || $type instanceof \ReflectionNamedType
                    && ( $type->getName() === 'int' || $type->getName() === 'string' )
                ) {
                    $signed[] = $name;
                }
            }
        }

        // Merge with explicit list
        return array_values(array_unique(array_merge($signed, $this->signedProperties())));
    }

    /**
     * Verify that the HMAC from the request matches the current signed property values.
     */
    private function verifyStateHmac(string $postedHmac): bool
    {
        $props = $this->resolveSignedProperties();
        if (empty($props)) {
            return true;
        }

        return hash_equals($this->buildHmac($props), $postedHmac);
    }

    /**
     * Build HMAC string from signed property values + screen class name.
     */
    private function buildHmac(array $properties): string
    {
        $payload = [];
        foreach ($properties as $prop) {
            $payload[$prop] = property_exists($this, $prop) ? $this->{$prop} : null;
        }

        // Include screen class to prevent cross-screen replay attacks
        $payload['__screen'] = static::class;

        $key = function_exists('encrypt') ? encrypt()->getKey() : 'flute-screen-state';

        return hash_hmac('sha256', json_encode($payload, JSON_THROW_ON_ERROR), $key);
    }
}
