<?php

namespace Flute\Core\Services;

use Flute\Core\Events\ResponseEvent;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Сервис для работы с куками (cookies) в Flute.
 */
class CookieService
{
    protected FluteRequest $request; // Текущий HTTP-запрос.
    protected EventDispatcher $eventDispatcher; // Диспетчер событий.
    protected array $cookies = []; // Массив куков, установленных в рамках текущего запроса.
    protected array $localCookies = []; // Массив локальных куков.

    /**
     * Конструктор класса.
     * 
     * @param FluteRequest $request Текущий HTTP-запрос.
     * @param EventDispatcher $eventDispatcher Диспетчер событий.
     */
    public function __construct(FluteRequest $request, EventDispatcher $eventDispatcher)
    {
        $this->request = $request;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Возвращает значение куки с указанным именем.
     * 
     * @param string $name Имя куки.
     * @param mixed $default Значение по умолчанию, возвращаемое в случае, если куки с указанным именем не существует.
     * @return mixed
     */
    public function get(string $name, $default = null)
    {
        if (isset($this->localCookies[$name])) {
            return $this->localCookies[$name];
        }

        return $this->request->cookies->get($name, $default);
    }

    /**
     * Устанавливает куки с указанными параметрами.
     * 
     * @param string $name Имя куки.
     * @param string $value Значение куки.
     * @param \DateTime|int|null $expire Время жизни куки (в секундах).
     * @param string $path Путь на сервере, для которого куки действительна.
     * @param string|null $domain Домен, для которого куки действительна.
     * @param bool $secure Флаг, указывающий, что куки должна быть передана только через защищенное соединение.
     * @param bool $httpOnly Флаг, указывающий, что куки должна быть доступна только через HTTP-запросы.
     * @return void
     */
    public function set(string $name, string $value, $expire = null, string $path = '/', string $domain = null, bool $httpOnly = true)
    {
        $cookie = new Cookie($name, $value, $this->getDateTime($expire), $path, $domain, request()->getScheme() === 'https', $httpOnly, true, 'Strict');
        $this->cookies[] = $cookie;
        $this->localCookies[$name] = $value;
    }

    /**
     * Проверяет, существует ли куки с указанным именем.
     * 
     * @param string $name Имя ку
     * @return bool
     */
    public function has(string $name): bool
    {
        return $this->request->cookies->has($name);
    }

    /**
     * Удаляет куки с указанным именем.
     * 
     * @param string $name Имя куки.
     * @param string $path Путь на сервере, для которого куки действительна.
     * @param string|null $domain Домен, для которого куки действительна.
     * @return void
     */
    public function remove(string $name, string $path = '/', string $domain = null)
    {
        $this->set($name, '', now()->modify('-3600 seconds'), $path, $domain);
    }

    /**
     * Добавляет все установленные в текущем запросе куки в заголовки ответа.
     * 
     * @param ResponseEvent $event Объект события ответа.
     * @return void
     */
    public function onResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        
        foreach ($this->cookies as $cookie) {
            $response->headers->setCookie($cookie);
        }

        $event->setResponse($response);
    }

    /**
     * Отдает корректный DateTime для установки куки
     * 
     * @param \DateTime|int|null $expire
     * 
     * @return \DateTime
     */
    protected function getDateTime($expire)
    {
        if ($expire instanceof \DateTime) {
            return $expire;
        }

        if( is_int($expire) ) {
            return now()->modify("+{$expire} seconds");
        }

        return now()->modify("+30 days");
    }
}
