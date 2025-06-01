<?php

namespace Flute\Core\Support;

use Flute\Core\Exceptions\TooManyRequestsException;
use Flute\Core\Support\Htmx\HtmxControllerTrait;
use Flute\Core\Support\Htmx\Response\HtmxResponse;
use Flute\Core\Validator\FluteValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseController
{
    use HtmxControllerTrait;

    protected array $middlewares = [];

    /**
     * Return error for API or View
     * 
     * @param string|array $message Error message
     * @param int $status HTTP Status code
     * 
     * @return Response
     */
    public function error($message, int $status = 403) : Response
    {
        /** @var FluteRequest $request */
        $request = app(FluteRequest::class);

        if ($request->expectsJson() || $request->isAjax())
            return json([
                "error" => $message,
            ], $status);

        return response()->error($status, $message);
    }

    /**
     * @return ErrorHandler
     */
    public function errors() : ErrorHandler
    {
        return new ErrorHandler($this);
    }

    /**
     * Common method for generating an error response
     *
     * @param string|null $message Error message
     * @param int $status HTTP status code
     * @return Response
     */
    public function respondWithError(string $message = null, int $status = 403) : Response
    {
        return $this->error($message, $status);
    }

    /**
     * Return success for API
     * 
     * @param string|array $message Error message
     * @param int $status HTTP Status code
     * 
     * @return JsonResponse
     */
    public function success($message = null, int $status = 200) : JsonResponse
    {
        return json([
            "success" => $message,
        ], $status);
    }

    /**
     * Return flash message
     *
     * @param string $message Error message
     * @param string $type
     * @return void
     */
    public function flash(string $message, string $type = 'error') : void
    {
        flash()->add($type, $message);
    }

    /**
     * Return toast message
     */
    public function toast(string $message, string $type = 'error') : void
    {
        toast()->$type($message)->push();
    }

    /**
     * Return json response
     * 
     * @param array $data
     * @param int $status
     * 
     * @return JsonResponse
     */
    public function json(array $data, int $status = 200) : JsonResponse
    {
        return response()->json($data, $status);
    }

    /**
     * Check if csrf token is valid
     * 
     * @return bool
     */
    public function isCsrfValid() : bool
    {
        return template()->getBlade()->csrfIsValid();
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
    protected function throttle(string $key, int $maxRequest = 5, int $perMinute = 60, int $burstiness = 5) : void
    {
        throttler()->throttle(
            ['action' => $key, request()->ip()],
            $maxRequest,
            $perMinute,
            $burstiness
        );
    }

    /**
     * Validate request parameters for presence and non-emptiness.
     * 
     * @param array $requestParams Parameters to validate.
     * @param array $requiredParams List of required parameter keys.
     * 
     * @return FluteValidator|bool|JsonResponse
     */
    protected function validate(array $requestParams, array $requiredParams, array $messages = [], string $prefix = null) : FluteValidator|bool|JsonResponse
    {
        $validator = validator();

        $validated = $validator->validate($requestParams, $requiredParams, $messages, $prefix);

        if (!$validator->hasErrors())
            return true;

        if (request()->expectsJson() || request()->isAjax()) {
            return $this->json($validator->getErrors()->toArray(), 422);
        }

        return $validator;
    }

    protected function htmx()
    {
        return response()->htmx();
    }

    protected function htmxRender(string $view, array $parameters = [], HtmxResponse $response = null) : HtmxResponse
    {
        $content = render($view, $parameters);

        if ($response) {
            $response->setContent($content);
            return $response;
        }

        return new HtmxResponse($content);
    }
}