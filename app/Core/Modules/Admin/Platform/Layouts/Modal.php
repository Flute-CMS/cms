<?php

namespace Flute\Admin\Platform\Layouts;

use Flute\Admin\Platform\Commander;
use Flute\Admin\Platform\Layout;
use Flute\Admin\Platform\Repository;
use Flute\Core\Support\FluteStr;

/**
 * Class Modal.
 */
class Modal extends Layout
{
    use Commander;

    public const SIZE_XL = 'xl';
    public const SIZE_LG = 'lg';
    public const SIZE_SM = 'sm';

    public const TYPE_CENTER = '';
    public const TYPE_RIGHT = 'right';

    /**
     * The modal window variation key,
     * for example, on the right, in the center.
     *
     * @var string
     */
    protected $type = self::TYPE_CENTER;

    /**
     * The size of the modal window,
     * for example, large or small.
     *
     * @var string
     */
    protected $size;

    /**
     * @var string
     */
    protected $template = 'admin::partials.layouts.modal';
    protected $repository;

    /**
     * Modal constructor.
     */
    public function __construct(Repository $repository, array $layouts = [])
    {
        $this->variables = [
            'apply' => __('def.apply'),
            'close' => __('def.close'),
            'size' => '',
            'type' => self::TYPE_CENTER,
            'modalId' => $repository->get('modalId'),
            'key' => FluteStr::slug($repository->get('modalId')),
            'params' => $repository->get('modalParams') !== null ? $repository->get('modalParams')->toArray() : [],
            'title' => $repository->get('modalId'),
            'commandBar' => [],
            'withoutApplyButton' => false,
            'withoutCloseButton' => false,
            'removeOnClose' => true,
            'open' => true,
            'method' => null,
        ];

        $this->layouts = $layouts;
        $this->query = $repository;
    }

    public function getSlug() : string
    {
        return $this->variables['key'];
    }

    public function right() : self
    {
        $this->variables['type'] = self::TYPE_RIGHT;
        return $this;
    }

    /**
     * @return mixed
     */
    public function build(Repository $repository)
    {
        return $this->buildAsDeep($repository);
    }

    /**
     * Set the text button for apply action.
     */
    public function applyButton(string $text) : self
    {
        $this->variables['apply'] = $text;

        return $this;
    }

    public function removeOnClose(bool $close = true) : self
    {
        $this->variables['removeOnClose'] = $close;
        return $this;
    }

    /**
     * Whether to disable the applied button or not.
     */
    public function withoutApplyButton(bool $withoutApplyButton = true) : self
    {
        $this->variables['withoutApplyButton'] = $withoutApplyButton;

        return $this;
    }

    /**
     * Whether to disable the close button or not.
     */
    public function withoutCloseButton(bool $withoutCloseButton = true) : self
    {
        $this->variables['withoutCloseButton'] = $withoutCloseButton;

        return $this;
    }

    /**
     * Set the text button for cancel action.
     */
    public function closeButton(string $text) : self
    {
        $this->variables['close'] = $text;

        return $this;
    }

    /**
     * Set CSS class for size modal.
     */
    public function size(string $class) : self
    {
        $this->variables['size'] = $class;

        return $this;
    }

    public function type(string $class) : self
    {
        $this->variables['type'] = $class;

        return $this;
    }

    public function method(string $method) : self
    {
        $this->variables['method'] = $method;
        return $this;
    }

    /**
     * Set title for header modal.
     */
    public function title(string $title) : self
    {
        $this->variables['title'] = $title;

        return $this;
    }

    public function params(array $params) : self
    {
        $this->variables['params'] = $params;
        return $this;
    }

    /**
     * @return $this
     */
    public function open(bool $status = true) : self
    {
        $this->variables['open'] = $status;

        return $this;
    }
}
