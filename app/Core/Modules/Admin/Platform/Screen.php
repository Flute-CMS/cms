<?php

namespace Flute\Admin\Platform;

use Clickfwd\Yoyo\Exceptions\BypassRenderMethod;
use Clickfwd\Yoyo\YoyoHelpers;
use Flute\Admin\Platform\Contracts\ScreenInterface;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Core\Contracts\FluteComponentInterface;
use Flute\Core\Support\FluteComponent;
use Illuminate\Support\Arr;

/**
 * Abstract class Screen.
 *
 * Base class for creating screens in the admin panel.
 */
abstract class Screen extends FluteComponent implements ScreenInterface
{
    protected $additionalLayouts;

    public $modalId = null;
    public $slug = null;
    public $modalParams = null;
    public $js = [];
    public $css = [];

    public array $excludesVariables = [
        'name',
        'description',
        'popover',
        'permission',
        'js',
        'css',
    ];

    public function boot(array $variables, array $attributes): FluteComponentInterface
    {
        parent::boot($variables, $attributes);

        if ($this->modalParams !== null && $this->modalParams !== 'null') {
            $this->modalParams = collect(is_string($this->modalParams) ? encrypt()->decrypt($this->modalParams) : $this->modalParams);
        } else {
            $this->modalParams = null;
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
     *
     * @return string
     */
    public function screenBaseView(): string
    {
        return 'admin::layouts.base';
    }

    /**
     * The name of the screen to be displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->name ?? null;
    }

    /**
     * Get the JavaScript files to be loaded.
     *
     * @return array
     */
    public function getJs(): array
    {
        return $this->js;
    }

    /**
     * Get the CSS files to be loaded.
     *
     * @return array
     */
    public function getCss(): array
    {
        return $this->css;
    }

    /**
     * A description of the screen to be displayed in the header.
     *
     * @return string|null
     */
    public function description(): ?string
    {
        return $this->description ?? null;
    }

    /**
     * The popover text for the screen to be displayed in the header.
     *
     * @return string|null
     */
    public function popover(): ?string
    {
        return $this->get('popover');
    }

    /**
     * The permissions required to access this screen.
     *
     * @return iterable|null
     */
    public function permission(): ?iterable
    {
        return isset($this->permission)
            ? Arr::wrap($this->permission)
            : null;
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
     *
     * @return array
     */
    abstract public function layout(): array;

    /**
     * Builds the screen using the given data repository.
     *
     * @param \Flute\Admin\Platform\Repository $repository
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

    public function openModal(string $modalFunc, $params = null)
    {
        if (!is_callable([$this, $modalFunc])) {
            throw new \Exception('Modal '.$modalFunc.' function is not callable');
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

        if (method_exists($this, $computedMethodName = 'get'.$studlyProperty.'Property')) {
            if (isset($this->computedPropertyCache[$property])) {
                return $this->computedPropertyCache[$property];
            }

            return $this->computedPropertyCache[$property] = $this->$computedMethodName();
        }

        return $default;
    }

    // clear opcache & jit cache
    public function clearOpcache()
    {
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }
}
