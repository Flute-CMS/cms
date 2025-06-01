<?php

namespace Flute\Admin\Platform\Fields;

use Flute\Admin\Platform\Field;

/**
 * Class RichText.
 *
 * @method RichText accesskey($value = true)
 * @method RichText autofocus($value = true)
 * @method RichText disabled($value = true)
 * @method RichText form($value = true)
 * @method RichText maxlength(int $value)
 * @method RichText name(string $value = null)
 * @method RichText placeholder(string $value = null)
 * @method RichText readonly($value = true)
 * @method RichText required(bool $value = true)
 * @method RichText tabindex($value = true)
 * @method RichText help(string $value = null)
 * @method RichText title(string $value = null)
 * @method RichText label(string $value = null)
 * @method RichText height(int $value = null)
 * @method RichText toolbar(array $value = null)
 * @method RichText spellcheck(bool $value = null)
 * @method RichText enableImageUpload(bool $value = true)
 * @method RichText imageUploadEndpoint(string $value = null)
 */
class RichText extends Field
{
    /**
     * Field view name
     *
     * @var string
     */
    protected $view = 'admin::partials.fields.richtext';

    /**
     * Attributes that should be treated as boolean.
     *
     * @var array
     */
    protected $booleanAttributes = [
        'required',
        'autofocus',
        'readonly',
        'disabled',
        'spellcheck',
        'enableImageUpload',
    ];

    /**
     * Editor height
     *
     * @var int
     */
    protected $height = 300;

    /**
     * Editor toolbar configuration
     *
     * @var array|null
     */
    protected $toolbar = null;

    /**
     * Enable image upload
     *
     * @var bool
     */
    protected $enableImageUpload = false;

    /**
     * Image upload endpoint
     *
     * @var string
     */
    protected $imageUploadEndpoint = '/admin/api/upload-image';

    /**
     * Default attributes value.
     *
     * @var array
     */
    protected $attributes = [
        'class'      => 'form-control markdown-editor',
        'value'      => null,
        'height'     => 300,
        'spellcheck' => false,
    ];

    /**
     * Attributes available for a particular tag.
     *
     * @var array
     */
    protected $inlineAttributes = [
        'accesskey',
        'autofocus',
        'disabled',
        'form',
        'maxlength',
        'name',
        'placeholder',
        'readonly',
        'required',
        'tabindex',
        'id',
        'height',
        'spellcheck',
        'data-height',
        'data-toolbar',
        'data-upload',
        'data-upload-url',
        'data-spellcheck',
    ];

    /**
     * Sets the text label for the rich text editor.
     *
     * @param string $value
     * @return $this
     */
    public function label(string $value)
    {
        $this->set('label', $value);
        return $this;
    }

    /**
     * Sets the height of the rich text editor.
     *
     * @param int $value
     * @return $this
     */
    public function height(int $value)
    {
        $this->height = $value;
        $this->set('height', $value);
        return $this;
    }

    /**
     * Sets custom toolbar configuration for the editor.
     *
     * @param array $value
     * @return $this
     */
    public function toolbar(array $value)
    {
        $this->toolbar = $value;
        return $this;
    }

    /**
     * Enables or disables spellcheck in the editor.
     *
     * @param bool $value
     * @return $this
     */
    public function spellcheck(bool $value = true)
    {
        $this->set('spellcheck', $value);
        return $this;
    }

    /**
     * Enables image upload functionality.
     *
     * @param bool $value
     * @return $this
     */
    public function enableImageUpload(bool $value = true)
    {
        $this->enableImageUpload = $value;
        return $this;
    }

    /**
     * Sets the image upload endpoint URL.
     *
     * @param string $value
     * @return $this
     */
    public function imageUploadEndpoint(string $value)
    {
        $this->imageUploadEndpoint = $value;
        return $this;
    }

    /**
     * Customize toolbar with specific options.
     * Provides shortcuts for common toolbar configurations.
     *
     * @param string $preset - One of 'basic', 'standard', 'full', 'minimal'
     * @return $this
     */
    public function toolbarPreset(string $preset = 'standard')
    {
        switch ($preset) {
            case 'minimal':
                $this->toolbar = [
                    'bold', 'italic', '|', 
                    'unordered-list', 'ordered-list', '|', 
                    'link', '|', 'preview'
                ];
                break;
            case 'basic':
                $this->toolbar = [
                    'bold', 'italic', 'heading', '|',
                    'quote', 'unordered-list', 'ordered-list', '|',
                    'link', 'image', '|',
                    'preview', 'guide',
                ];
                break;
            case 'full':
                $this->toolbar = [
                    'bold', 'italic', 'strikethrough', 'heading', 'heading-smaller', 'heading-bigger', 
                    '|', 
                    'code', 'quote', 'unordered-list', 'ordered-list', 'horizontal-rule',
                    '|',
                    'link', 'image', 'table',
                    '|',
                    'preview', 'side-by-side', 'fullscreen',
                    '|',
                    'guide',
                ];
                break;
            case 'standard':
            default:
                $this->toolbar = [
                    'bold', 'italic', 'heading', '|',
                    'quote', 'unordered-list', 'ordered-list', '|',
                    'link', 'image', '|',
                    'preview', 'side-by-side', 'fullscreen',
                    '|',
                    'guide',
                ];
                break;
        }
        
        return $this;
    }

    /**
     * Get the field's attributes
     * 
     * @return array
     */
    public function getAttributes(): array
    {
        $attributes = parent::getAttributes();
        $attributes['data-height'] = $this->height;
        $attributes['data-spellcheck'] = $this->get('spellcheck') ? 'true' : 'false';
        
        if ($this->toolbar !== null) {
            $attributes['data-toolbar'] = json_encode($this->toolbar);
        }
        
        if ($this->enableImageUpload) {
            $attributes['data-upload'] = 'true';
            $attributes['data-upload-url'] = $this->imageUploadEndpoint;
        }
        
        return $attributes;
    }
} 