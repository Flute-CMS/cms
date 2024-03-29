<?php

namespace Flute\Core\Services;

use Cycle\ORM\RepositoryInterface;
use Flute\Core\Database\Entities\Notification;
use Flute\Core\Exceptions\NotAuthenticatedException;
use Flute\Core\Http\Controllers\NotificationController;
use Flute\Core\Http\Middlewares\isAuthenticatedMiddleware;
use Flute\Core\Router\RouteDispatcher;
use Flute\Core\Router\RouteGroup;
use Throwable;

/**
 * Class NotificationService
 *
 * Provides functionality for managing notifications for the current user.
 */
class NotificationService
{
    /**
     * @var RepositoryInterface
     */
    protected RepositoryInterface $noteRepository;

    /**
     * @var array
     * Array to cache notifications.
     */
    protected array $cachedItems;

    /**
     * @var bool
     * A flag to check whether items are cached or not.
     */
    protected bool $cached = false;

    protected RouteDispatcher $dispatcher;

    /**
     * NotificationService constructor.
     * Initializes the Notification repository.
     */
    public function __construct( RouteDispatcher $routeDispatcher )
    {
        $this->dispatcher = $routeDispatcher;
        $this->noteRepository = rep(Notification::class);
        $this->setRoutes();
    }

    /**
     * Get all notifications for the current user.
     *
     * @param bool $byDate - Split notifications by date.
     * 
     * @return array List of notifications.
     */
    public function all(bool $byDate = false) : array
    {
        if ($this->cached) {
            return $byDate ? $this->splitByDate($this->cachedItems) : $this->cachedItems;
        }

        $items = $this->noteRepository->select()->columns('content', 'created_at', 'icon', 'id', 'title', 'url', 'viewed')->where([
            'user_id' => user()->id,
        ])->fetchAll();

        $this->cached = true;
        $this->cachedItems = $items;

        return $byDate ? $this->splitByDate($items) : $items;
    }

    /**
     * Get all unread notifications for the current user.
     * 
     * @param bool $byDate - Split notifications by date.
     * 
     * @return array List of unread notifications.
     */
    public function unread(bool $byDate = false) : array
    {
        $items = $this->all();

        $items = array_filter($items, function ($item) {
            return !$item->viewed;
        });
        
        return $byDate ? $this->splitByDate($items) : $items;
    }

    /**
     * Get all notifications split by date.
     * 
     * @param array $list List of notifications
     * 
     * @return array List of notifications split by date.
     */
    protected function splitByDate(array $list) : array
    {
        $result = [];

        foreach ($list as $subArray) {
            $date = $subArray->created_at->format('d.m.Y');

            // #TODO: temporary
            unset($subArray->user);

            // if (!isset($groupedArray[$date])) {
            //     $result[$date] = [];
            // }
        
            $result[$date][] = $subArray;
        }

        return $result;
    }

    /**
     * Set a notification as viewed.
     *
     * @param int $id Notification id.
     * @throws Throwable
     */
    public function setViewed(int $id) : void
    {
        $note = $this->noteRepository->findOne([
            'user_id' => user()->id,
            'id' => $id
        ]);
        $note->viewed = true;

        transaction($note)->run();
    }

    /**
     * Delete a notification.
     *
     * @param int $id Notification id.
     * @throws Throwable
     */
    public function delete(int $id) : void
    {
        $note = $this->noteRepository->findOne([
            'user_id' => user()->id,
            'id' => $id
        ]);

        transaction($note, 'delete')->run();
    }

    /**
     * Create a new notification.
     *
     * @param Notification $note Notification to be created.
     * @throws Throwable
     */
    public function create(Notification $note) : void
    {
        transaction($note)->run();
    }

    /**
     * Refresh the cache of notifications.
     */
    public function refresh() : void
    {
        $this->cached = false;
        $this->cachedItems = $this->all();
    }

    /**
     * Clear all notifications.
     * Note: this method can be optimized.
     * @throws Throwable
     */
    public function clear() : void
    {
        $items = $this->all();

        foreach ($items as $item) {
            $this->delete($item->id);
        }
    }

    /**
     * Count the number of notifications for the current user.
     *
     * @return int Number of notifications.
     */
    public function count() : int
    {
        return sizeof($this->unread());
    }

    protected function setRoutes() : void
    {
        $this->dispatcher->group(function (RouteGroup $routeGroup) {
            $routeGroup->middleware(isAuthenticatedMiddleware::class);

            $routeGroup->get('/all', [NotificationController::class, 'getAll']);
            $routeGroup->get('/unread', [NotificationController::class, 'getUnread']);
            $routeGroup->delete('/{id<\d+>}', [NotificationController::class, 'delete']);
            $routeGroup->put('/{id<\d+>}', [NotificationController::class, 'read']);
            $routeGroup->delete('', [NotificationController::class, 'clear']);
        }, "api/notifications");
    }
}
