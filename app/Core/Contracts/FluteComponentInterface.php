<?php

namespace Flute\Core\Contracts;

use Clickfwd\Yoyo\Interfaces\ViewProviderInterface;

interface FluteComponentInterface
{
    /**
     * Initialize the component with variables and attributes.
     *
     * @param array $variables  Component variables.
     * @param array $attributes HTML attributes for the component.
     * @return self
     */
    public function boot(array $variables, array $attributes);

    /**
     * Render the component's view.
     *
     * @return ViewProviderInterface|string
     */
    public function render();

    /**
     * Set data to be passed to the view.
     *
     * @param string|array $key   Key or associative array of data.
     * @param mixed|null   $value Value if key is provided.
     * @return self
     */
    public function set($key, $value = null);

    /**
     * Validate component data against rules.
     *
     * @param array      $rules    Validation rules.
     * @param array|null $data     Data to validate.
     * @param array      $messages Custom error messages.
     * @return bool True if valid, false otherwise.
     */
    public function validate(array $rules, array $data = null, array $messages = []);

    /**
     * Redirect to a specified URL with optional delay.
     *
     * @param string $url   Target URL.
     * @param int    $delay Delay in milliseconds.
     * @return void
     */
    public function redirectTo(string $url, int $delay = 0);

    /**
     * Flash a message to the user.
     *
     * @param string $message The message content.
     * @param string $type    The type of message (e.g., 'success', 'error').
     * @return void
     */
    public function flashMessage(string $message, string $type = 'success');

    /**
     * Skip rendering and respond with a specific HTTP status code.
     *
     * @param int $statusCode HTTP status code.
     * @return void
     */
    public function skipRenderWithStatus(int $statusCode = 204);

    /**
     * Emit an event to other components or the browser.
     *
     * @param string $event Event name.
     * @param mixed  ...$args Event arguments.
     * @return void
     */
    public function emitEvent(string $event, ...$args);

    /**
     * Dispatch a browser event.
     *
     * @param string $event Event name.
     * @param mixed  $data  Data to pass with the event.
     * @return void
     */
    public function dispatchBrowserEvent($event, $data = null);
}
