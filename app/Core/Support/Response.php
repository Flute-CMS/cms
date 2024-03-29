<?php

namespace Flute\Core\Support;

use Flute\Core\Template\Template;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Response
{
    protected Template $template;

    public function __construct(Template $template)
    {
        $this->template = $template;
    }

    /**
     * Return success for API
     * 
     * @param array|string $message
     * @param int $status HTTP Status code
     * 
     * @return JsonResponse
     */
    public function success($message = 'success', int $status = 200): JsonResponse
    {
        return json([
            "success" => $message,
        ], $status);
    }

    /**
     * Вернуть шаблон с ошибкой
     *
     * @param int $status
     * @param array|string $message
     *
     * @param string|null $message
     * @return HttpFoundationResponse
     */
    public function error(int $status = 404, $message = null): HttpFoundationResponse
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
     * Создает новый HTTP-ответ с заданным контентом.
     *
     * @param string $content Контент, который нужно отправить в ответе
     * @param int $status Числовой HTTP-код ответа
     * @param array $headers Ассоциативный массив с дополнительными заголовками ответа
     *
     * @return HttpFoundationResponse Объект типа Response
     */
    public function make(string $content = '', int $status = 200, array $headers = []): HttpFoundationResponse
    {
        $response = new HttpFoundationResponse($content, $status, $headers);

        if (config('app.mode') === \Flute\Core\App::PERFORMANCE_MODE)
            $response->headers->set('Cache-Control', 'max-age=86400');

        return $response;
    }

    /**
     * Создает новый HTTP-ответ в формате JSON.
     *
     * @param mixed $data Данные, которые нужно стерилизовать в JSON
     * @param int $status Числовой HTTP-код ответа
     * @param array $headers Ассоциативный массив с дополнительными заголовками ответа
     * @param bool $json Определяет, нужно ли выводить данные в удобочитаемом формате
     *
     * @return JsonResponse Объект типа JsonResponse
     */
    public function json($data, int $status = 200, array $headers = [], bool $json = false): JsonResponse
    {
        return new JsonResponse($data, $status, $headers, $json);
    }

    /**
     * Создает новый HTTP-ответ, который перенаправляет запрос на другой адрес.
     *
     * @param string $url Адрес, на который нужно перенаправить запрос
     * @param int $status Числовой HTTP-код ответа
     * @param array $headers Ассоциативный массив с дополнительными заголовками ответа
     *
     * @return RedirectResponse Объект типа RedirectResponse
     */
    public function redirect(string $url, int $status = 302, array $headers = [], bool $redirectForced = false): RedirectResponse
    {
        $redirect = new RedirectResponse($url, $status, $headers);

        return $redirectForced ? $redirect->send() : $redirect;
    }

    /**
     * Создает новый HTTP-ответ, который отправляет файл клиенту.
     *
     * @param string $file Путь к файлу, который нужно отправить
     * @param int $status Числовой HTTP-код ответа
     * @param array $headers Ассоциативный массив с дополнительными заголовками ответа
     * @param bool $public Определяет, можно ли кешировать файл в браузере клиента
     * @param string|null $contentDisposition Строка, содержащая значение заголовка Content-Disposition
     * @param bool $autoEtag Определяет, нужно ли автоматически генерировать ETag для файла
     * @param bool $autoLastModified Определяет, нужно ли автоматически генерировать заголовок Last-Modified для файла
     *
     * @return BinaryFileResponse Объект типа BinaryFileResponse
     */
    public function file(string $file, int $status = 200, array $headers = [], bool $public = true, string $contentDisposition = null, bool $autoEtag = false, bool $autoLastModified = true): BinaryFileResponse
    {
        return new BinaryFileResponse($file, $status, $headers, $public, $contentDisposition, $autoEtag, $autoLastModified);
    }

    /**
     * Создает новый HTTP-ответ, который передает данные потоком.
     *
     * @param callable $callback Функция обратного вызова, которая будет вызвана при генерации контента ответа
     * @param int $status Числовой HTTP-код ответа
     * @param array $headers Ассоциативный массив с дополнительными заголовками ответа
     *
     * @return StreamedResponse Объект типа StreamedResponse
     */
    public function streamable(callable $callback, int $status = 200, array $headers = []): StreamedResponse
    {
        return new StreamedResponse($callback, $status, $headers);
    }

    /**
     * Создает новый HTTP-ответ с пустым телом и указанным HTTP-кодом.
     *
     * @param int $status Числовой HTTP-код ответа
     * @param array $headers Ассоциативный массив с дополнительными заголовками ответа
     *
     * @return HttpFoundationResponse Объект типа Response
     */
    public function noContent(int $status = 204, array $headers = []): HttpFoundationResponse
    {
        return new HttpFoundationResponse('', $status, $headers);
    }
}