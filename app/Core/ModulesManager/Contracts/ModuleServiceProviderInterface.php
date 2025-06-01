<?php

namespace Flute\Core\ModulesManager\Contracts;

/**
 * Интерфейс для поставщиков услуг модулей.
 * Определяет необходимые методы, которые должен реализовать каждый модуль.
 */
interface ModuleServiceProviderInterface
{
    /**
     * Вызывается только тогда, когда модуль активен
     * 
     * @param \DI\Container $container Контейнер для регистрации сервисов
     */
    public function boot(\DI\Container $container): void;

    /**
     * Вызывается при регистрации модуля в системе
     * 
     * @param \DI\Container $container Контейнер для регистрации сервисов
     */
    public function register(\DI\Container $container): void;

    /**
     * Получить слушателей событий.
     * 
     * @return array Массив слушателей событий
     */
    public function getEventListeners(): array;

    /**
     * Проверить, должны ли вызываться расширения.
     * 
     * @return bool Возвращает true, если расширения должны быть вызваны
     */
    public function isExtensionsCallable(): bool;

    /**
     * Установить имя модуля.
     * 
     * @param string $moduleName Имя модуля
     */
    public function setModuleName(string $moduleName): void;

    /**
     * Получить имя модуля.
     * 
     * @return string Имя модуля
     */
    public function getModuleName(): string;
}
