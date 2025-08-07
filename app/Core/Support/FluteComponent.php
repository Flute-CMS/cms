<?php

namespace Flute\Core\Support;

use Clickfwd\Yoyo\ClassHelpers;
use Clickfwd\Yoyo\Component;
use Clickfwd\Yoyo\Exceptions\BypassRenderMethod;
use Flute\Core\Contracts\FluteComponentInterface;
use Flute\Core\Exceptions\TooManyRequestsException;

/**
 * Class FluteComponent
 *
 * Extends the base Component to provide additional utility methods.
 */
abstract class FluteComponent extends Component implements FluteComponentInterface
{
    public array $confirmedActions = [];

    protected array $excludesVariables = [];

    protected $validator;

    public function boot(array $variables, array $attributes)
    {
        $data = array_merge($variables, $this->request->all());

        $this->variables = $variables;

        $this->attributes = $attributes;

        $publicProperties = ClassHelpers::getPublicProperties($this, __CLASS__);

        $this->validator = validator();

        foreach ($publicProperties as $property) {
            if (!in_array($property, $this->excludesVariables)) {
                $this->{$property} = $data[$property] ?? $this->{$property};
            }
        }

        foreach ($this->getDynamicProperties() as $property) {
            if (!in_array($property, $this->excludesVariables)) {
                $this->{$property} = $data[$property] ?? null;
            }
        }

        return $this;
    }

    /**
     * Validate component data against rules.
     *
     * @param array      $rules
     * @param array|null $data
     * @param array      $messages
     * @return bool
     */
    public function validate(array $rules, array $data = null, array $messages = [])
    {
        $data ??= $this->getPublicProperties();

        return $this->validator->validate($data, $rules, $messages, null);
    }

    /**
     * Get the validator errors.
     *
     * @return \Illuminate\Support\MessageBag
     */
    public function getValidatorErrors()
    {
        return $this->validator->getErrors();
    }

    /**
     * Get the validator instance.
     *
     * @return \Flute\Core\Validator\FluteValidator
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * Show confirmation dialog.
     *
     * @param string $actionKey The action key
     * @param string $type The confirmation type (accent, primary, error, warning, info)
     * @param string $message The confirmation message
     * @param string|null $title The confirmation dialog title
     * @param string|null $confirmText The text for the confirm button
     * @param string|null $cancelText The text for the cancel button
     * @param bool|null $withoutTrigger Whether to hide the trigger element
     * @return void
     */
    public function confirm(string $actionKey, string $type, string $message, ?string $title = null, ?string $confirmText = null, ?string $cancelText = null, ?bool $withoutTrigger = false)
    {
        $action = $this->request->get('component') ?? '';
        $action = explode('/', $action)[1] ?? '';

        $this->emit('confirm', [
            'actionKey' => $actionKey,
            'message' => $message,
            'title' => $title,
            'confirmText' => $confirmText,
            'cancelText' => $cancelText,
            'type' => $type,
            'action' => $action,
            'originalRequestData' => $this->request->all(),
            'withoutTrigger' => $withoutTrigger,
        ]);
    }

    /**
     * Check if an action has been confirmed.
     *
     * @param string $actionKey A unique key to identify this confirmed action
     *
     * @return bool
     */
    public function confirmed(string $actionKey): bool
    {
        /**
         * 1.  Проверяем клавиши, пришедшие вместе с запросом (старое‑поведение).
         * 2.  Дополнительно смотрим локальное свойство $this->confirmedActions,
         *     которое пополняется методом confirmAction().
         *     Это гарантия, что подтверждение «доживёт» до
         *     следующего запроса‑рендера даже без скрытых полей формы.
         */
        $fromRequest = $this->request->get('confirmed_action', '');
        $fromRequest = is_string($fromRequest) ? explode(',', $fromRequest) : (array) $fromRequest;

        return \in_array($actionKey, $fromRequest, true)
            || \in_array($actionKey, $this->confirmedActions, true);
    }

    /**
     * Throttle the requests to limit the number of attempts per minute.
     *
     * @param string $key The action key.
     * @param int $maxRequest The maximum number of requests allowed.
     * @param int $perMinute The time period in minutes.
     * @param int $burstiness The maximum number of requests in a burst.
     * @throws TooManyRequestsException
     */
    protected function throttle(string $key, int $maxRequest = 5, int $perMinute = 60, int $burstiness = 5): void
    {
        throttler()->throttle(
            ['action' => $key, request()->ip()],
            $maxRequest,
            $perMinute,
            $burstiness
        );
    }

    /**
     * Redirect to a specified URL with optional delay.
     *
     * @param string $url
     * @param int    $delay
     * @return void
     */
    public function redirectTo(string $url, int $delay = 0)
    {
        if ($delay > 0) {
            $this->dispatchBrowserEvent('delayed-redirect', [
                'url' => $url,
                'delay' => $delay,
            ]);
        } else {
            $this->redirect($url);
        }
    }

    /**
     * Get the response instance.
     */
    public function response()
    {
        return $this->response;
    }

    /**
     * Flash a message to the user.
     *
     * @param string $message
     * @param string $type
     * @return void
     */
    public function flashMessage(string $message, string $type = 'success')
    {
        toast()->$type($message)->push();
    }

    /**
     * Flash a error message in input.
     *
     * @param string $name
     * @param $error
     *
     * @return void
     */
    public function inputError(string $name, $error)
    {
        template()->addError($name, $error);
    }

    /**
     * Skip rendering and respond with a specific HTTP status code.
     *
     * @param int $statusCode
     * @return void
     *
     * @throws BypassRenderMethod
     */
    public function skipRenderWithStatus(int $statusCode = 204)
    {
        $this->response->status($statusCode);
        $this->omitResponse = true;
    }

    /**
     * Emit an event to other components or the browser.
     *
     * @param string $event
     * @param mixed  ...$args
     * @return void
     */
    public function emitEvent(string $event, ...$args)
    {
        $this->emit($event, ...$args);
    }

    /**
     * Set multiple properties at once.
     *
     * @param array $properties
     * @return self
     */
    public function setProperties(array $properties)
    {
        foreach ($properties as $key => $value) {
            $this->$key = $value;
        }

        return $this;
    }

    /**
     * Open a modal.
     *
     * @param string $modalId The ID of the modal to open.
     * @return void
     */
    public function modalOpen(string $modalId): void
    {
        $this->dispatchBrowserEvent('open-modal', [
            'modalId' => $modalId,
        ]);
    }

    /**
     * Close a modal.
     *
     * @param string $modalId The ID of the modal to close.
     * @return void
     */
    public function modalClose(string $modalId)
    {
        $this->dispatchBrowserEvent('close-modal', [
            'modalId' => $modalId,
        ]);
    }

    /**
     * Get public properties of the component.
     *
     * @return array
     */
    protected function getPublicProperties()
    {
        return $this->viewVars();
    }

    /**
     * Handle a request that requires confirmation.
     * If the request is not confirmed, it will show a confirmation dialog.
     * If it is confirmed, it will execute the action and return the result.
     *
     * @param string $actionKey The unique action key
     * @param string $type The confirmation type (accent, primary, error, warning, info)
     * @param string $message The confirmation message
     * @param callable $action The action to execute when confirmed
     * @param string|null $title The confirmation dialog title
     * @param string|null $confirmText The text for the confirm button
     * @param string|null $cancelText The text for the cancel button
     *
     * @return mixed The result of the action if confirmed
     */
    public function withConfirmation(string $actionKey, string $type, string $message, callable $action, ?string $title = null, ?string $confirmText = null, ?string $cancelText = null)
    {
        if (!$this->confirmed($actionKey)) {
            $this->confirm($actionKey, $type, $message, $title, $confirmText, $cancelText);

            return null;
        }

        return $action();
    }
}
