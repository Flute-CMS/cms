<?php

namespace Flute\Core\Support;

use Flute\Core\Support\Htmx\Response\HtmxClientRedirectResponse;
use Flute\Core\Support\Htmx\Response\HtmxClientRefreshResponse;
use Flute\Core\Support\Htmx\Response\HtmxResponse;
use Flute\Core\Support\Htmx\Response\HtmxStopPollingResponse;
use Flute\Core\Template\Template;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use \Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Response
{
    protected Template $template;
    protected SymfonyResponse $response;

    public function __construct(Template $template)
    {
        $this->template = $template;
        $this->response = new SymfonyResponse();
    }

    /**
     * Returns a successful JSON response.
     *
     * @param array|string $message
     * @param int $status
     *
     * @return JsonResponse
     */
    public function success($message = 'success', int $status = 200): JsonResponse
    {
        return $this->json([
            "success" => $message,
        ], $status);
    }

    /**
     * Returns an error page or JSON response based on the request type.
     *
     * @param int $status
     * @param string|null $message
     *
     * @return SymfonyResponse
     */
    public function error(int $status = 404, ?string $message = null): JsonResponse|SymfonyResponse
    {
        /** @var FluteRequest $request */
        $request = app(FluteRequest::class);

        if ($request->expectsJson() || $request->isAjax())
            return $this->json([
                "error" => $message,
            ], $status);

        return $this->make($this->template->renderError($status, [
            "message" => $message
        ]), $status);
    }

    /**
     * Adds a single header to the response.
     *
     * @param string $key
     * @param string $value
     *
     * @return $this
     */
    public function header(string $key, string $value): self
    {
        $this->response->headers->set($key, $value);
        return $this;
    }

    /**
     * Adds multiple headers to the response.
     *
     * @param array $headers
     *
     * @return $this
     */
    public function withHeaders(array $headers): self
    {
        foreach ($headers as $key => $value) {
            $this->header($key, $value);
        }
        return $this;
    }

    /**
     * Creates a response with the given content.
     *
     * @param string $content
     * @param int $status
     * @param array $headers
     *
     * @return SymfonyResponse
     */
    public function make(string $content = '', int $status = 200, array $headers = []): SymfonyResponse
    {
        $this->response->setContent($content);
        $this->response->setStatusCode($status);
        $this->withHeaders($headers);

        return $this->response;
    }

    /**
     * Returns a view response with data.
     *
     * @param string $view
     * @param array $data
     * @param int $status
     * @param array $headers
     *
     * @return SymfonyResponse
     */
    public function view(string $view, array $data = [], int $status = 200, array $headers = []): SymfonyResponse
    {
        $content = view($view, $data)->toHtml();
        return $this->make($content, $status, $headers);
    }

    /**
     * Returns a JSON response.
     *
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @param bool $json
     *
     * @return JsonResponse
     */
    public function json($data, int $status = 200, array $headers = [], bool $json = false): JsonResponse
    {
        return new JsonResponse($data, $status, $headers, $json);
    }

    /**
     * Redirects the request to a different URL.
     *
     * @param string $url
     * @param int $status
     * @param array $headers
     *
     * @return HtmxResponse
     */
    public function redirect(string $url, int $status = 302, array $headers = [], bool $redirectForced = false): RedirectResponse
    {
        $redirect = app()->make(RedirectResponse::class, ['to' => $url, 'status' => $status, 'headers' => $headers]);

        return $redirectForced ? $redirect->send() : $redirect;
    }

    /**
     * Forces a redirect and immediately halts execution.
     *
     * @param string $url
     * @param int $status
     * @param array $headers
     *
     * @return void
     */
    public function forceRedirect(string $url, int $status = 302, array $headers = []): void
    {
        $redirect = app()->make(RedirectResponse::class, ['to' => $url, 'status' => $status, 'headers' => $headers]);
        $redirect->send();
        exit;
    }

    /**
     * Sends a file to the client.
     *
     * @param string $file
     * @param int $status
     * @param array $headers
     *
     * @return BinaryFileResponse
     */
    public function file(string $file, int $status = 200, array $headers = []): BinaryFileResponse
    {
        return new BinaryFileResponse($file, $status, $headers);
    }

    /**
     * Streams content to the client.
     *
     * @param callable $callback
     * @param int $status
     * @param array $headers
     *
     * @return StreamedResponse
     */
    public function streamable(callable $callback, int $status = 200, array $headers = []): StreamedResponse
    {
        return new StreamedResponse($callback, $status, $headers);
    }

    /**
     * Returns a response with no content.
     *
     * @param int $status
     * @param array $headers
     *
     * @return SymfonyResponse
     */
    public function noContent(int $status = 204, array $headers = []): SymfonyResponse
    {
        return $this->make('', $status, $headers);
    }

    public function htmxRedirect(string $url): HtmxClientRedirectResponse
    {
        return new HtmxClientRedirectResponse($url);
    }

    public function htmxRefresh(): HtmxClientRefreshResponse
    {
        return new HtmxClientRefreshResponse();
    }

    public function htmxStopPolling(): HtmxStopPollingResponse
    {
        return new HtmxStopPollingResponse();
    }

    public function htmx(): HtmxResponse
    {
        return new HtmxResponse();
    }
}