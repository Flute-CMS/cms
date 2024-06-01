<?php

namespace Flute\Core\Admin\Services\PageGenerator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AdminFormPage
 *
 * Represents an admin form page with multiple input fields.
 */
class AdminFormPage
{
    private string $title;
    private string $description;
    private string $backUrl;
    private string $formType;
    private string $page;
    private array $inputs = [];

    /**
     * AdminFormPage constructor.
     *
     * @param string $title
     * @param string $description
     * @param string $backUrl
     * @param string $formType
     * @param string $page
     * @param array $inputs
     */
    public function __construct(string $title, string $description, string $backUrl, string $formType, string $page, array $inputs = [])
    {
        $this->title = $title;
        $this->description = $description;
        $this->backUrl = $backUrl;
        $this->formType = $formType;
        $this->page = $page;
        $this->inputs = $inputs;
    }

    /**
     * Get the title of the form page.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set the title of the form page.
     *
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Get the description of the form page.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Set the description of the form page.
     *
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * Get the back URL of the form page.
     *
     * @return string
     */
    public function getBackUrl(): string
    {
        return $this->backUrl;
    }

    /**
     * Set the back URL of the form page.
     *
     * @param string $backUrl
     */
    public function setBackUrl(string $backUrl): void
    {
        $this->backUrl = $backUrl;
    }

    /**
     * Get the form type.
     *
     * @return string
     */
    public function getFormType(): string
    {
        return $this->formType;
    }

    /**
     * Set the form type.
     *
     * @param string $formType
     */
    public function setFormType(string $formType): void
    {
        $this->formType = $formType;
    }

    /**
     * Get the page identifier.
     *
     * @return string
     */
    public function getPage(): string
    {
        return $this->page;
    }

    /**
     * Set the page identifier.
     *
     * @param string $page
     */
    public function setPage(string $page): void
    {
        $this->page = $page;
    }

    /**
     * Get the input fields of the form.
     *
     * @return array
     */
    public function getInputs(): array
    {
        return $this->inputs;
    }

    /**
     * Set the input fields of the form.
     *
     * @param array $inputs
     */
    public function setInputs(array $inputs): void
    {
        $this->inputs = $inputs;
    }

    /**
     * Add an input field to the form.
     *
     * @param AdminInput $input
     */
    public function addInput(AdminInput $input): void
    {
        $this->inputs[] = $input->toArray();
    }

    /**
     * Get the resulted page 
     * 
     * @return string
     */
    public function render() : Response
    {
        return view("Core/Admin/Http/Views/pages/generator/form", $this->toArray());
    }

    /**
     * Convert the form page to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'backUrl' => $this->backUrl,
            'formType' => $this->formType,
            'page' => $this->page,
            'inputs' => $this->inputs,
        ];
    }
}