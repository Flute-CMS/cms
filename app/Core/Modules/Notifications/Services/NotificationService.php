<?php

namespace Flute\Core\Modules\Notifications\Services;

use Cycle\ORM\RepositoryInterface;
use Flute\Core\Database\Entities\Notification;
use Flute\Core\Database\Entities\User;
use Throwable;

/**
 * Class NotificationService
 *
 * Provides functionality for managing the current user's notifications.
 */
class NotificationService
{
    /**
     */
    protected ?RepositoryInterface $noteRepository = null;

    /**
     * @var array
     * Cache of all notifications.
     */
    protected array $cachedItems = [];

    /**
     * @var bool
     * Flag indicating whether notifications have been loaded into the cache.
     */
    protected bool $cached = false;

    /**
     * @var array
     * Cache of unread notifications.
     */
    protected array $cachedUnreadItems = [];

    /**
     * @var bool
     * Flag indicating whether unread notifications have been loaded into the cache.
     */
    protected bool $unreadCached = false;

    /**
     * @var int|null
     * Cache of the count of unread notifications.
     */
    protected ?int $cachedUnreadCount = null;

    /**
     * @var int|null
     * Cache of the count of read notifications.
     */
    protected ?int $cachedReadCount = null;

    /**
     * @var int|null
     * Cache of the total count of notifications.
     */
    protected ?int $cachedTotalCount = null;

    /**
     * @var array
     * Cache of read notifications.
     */
    protected array $cachedReadItems = [];

    /**
     * @var bool
     * Flag indicating whether read notifications have been loaded into the cache.
     */
    protected bool $readCached = false;

    /**
     * Get all notifications for the current user.
     *
     * @param bool $byDate - Split notifications by date.
     *
     * @return array List of notifications.
     */
    public function all(bool $byDate = false): array
    {
        if ($this->cached) {
            return $byDate ? $this->splitByDate($this->cachedItems) : $this->cachedItems;
        }

        $items = Notification::query()
            ->where(['user_id' => user()->id])
            ->orderBy('createdAt', 'DESC')
            ->fetchAll();

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
    public function unread(bool $byDate = false): array
    {
        if ($this->unreadCached) {
            return $byDate ? $this->splitByDate($this->cachedUnreadItems) : $this->cachedUnreadItems;
        }

        $items = Notification::query()
            ->where([
                'user_id' => user()->id,
                'viewed' => false,
            ])
            ->orderBy('createdAt', 'DESC')
            ->fetchAll();

        $this->unreadCached = true;
        $this->cachedUnreadItems = $items;

        return $byDate ? $this->splitByDate($items) : $items;
    }

    /**
     * Get all read notifications for the current user.
     *
     * @param bool $byDate - Split notifications by date.
     *
     * @return array List of read notifications.
     */
    public function read(bool $byDate = false): array
    {
        if ($this->readCached) {
            return $byDate ? $this->splitByDate($this->cachedReadItems) : $this->cachedReadItems;
        }

        $items = Notification::query()
            ->where([
                'user_id' => user()->id,
                'viewed' => true,
            ])
            ->orderBy('createdAt', 'DESC')
            ->fetchAll();

        $this->readCached = true;
        $this->cachedReadItems = $items;

        return $byDate ? $this->splitByDate($items) : $items;
    }

    /**
     * Create a text notification.
     *
     * @param User $user The user to whom the notification is intended.
     * @param string $title The title of the notification.
     * @param string $content The content of the notification.
     * @param string|null $icon The icon of the notification.
     * @throws Throwable
     */
    public function createTextNotification(User $user, string $title, string $content, ?string $icon = null): void
    {
        $notification = new Notification();
        $notification->user = $user;
        $notification->title = $title;
        $notification->content = $content;
        $notification->type = 'text';
        $notification->icon = $icon;
        $this->create($notification);
    }

    /**
     * Create a notification with buttons.
     *
     * @param User $user The user to whom the notification is intended.
     * @param string $title The title of the notification.
     * @param string $content The content of the notification.
     * @param array $buttons Array of buttons.
     * @param string|null $icon The icon of the notification.
     * @throws Throwable
     */
    public function createButtonNotification(User $user, string $title, string $content, array $buttons, ?string $icon = null): void
    {
        $notification = new Notification();
        $notification->user = $user;
        $notification->title = $title;
        $notification->content = $content;
        $notification->type = 'button';
        $notification->extra_data = ['buttons' => $buttons];
        $notification->icon = $icon;
        $this->create($notification);
    }

    /**
     * Create a notification with a file.
     *
     * @param User $user The user to whom the notification is intended.
     * @param string $title The title of the notification.
     * @param string $content The content of the notification.
     * @param string $fileUrl The URL of the file.
     * @param string|null $icon The icon of the notification.
     * @throws Throwable
     */
    public function createFileNotification(User $user, string $title, string $content, string $fileUrl, ?string $icon = null): void
    {
        $notification = new Notification();
        $notification->user = $user;
        $notification->title = $title;
        $notification->content = $content;
        $notification->type = 'file';
        $notification->url = $fileUrl;
        $notification->icon = $icon;
        $this->create($notification);
    }

    /**
     * Mark a notification as viewed.
     *
     * @param int $id The identifier of the notification.
     * @throws Throwable
     */
    public function setViewed(int $id): void
    {
        $note = Notification::query()
            ->where([
                'user_id' => user()->id,
                'id' => $id,
            ])
            ->fetchOne();

        if ($note === null) {
            return;
        }

        if (!$note->viewed) {
            $note->viewed = true;
            transaction($note)->run();
            $this->invalidateCache();
        }
    }

    /**
     * Delete a notification.
     *
     * @param int $id The identifier of the notification.
     * @throws Throwable
     */
    public function delete(int $id): void
    {
        $note = Notification::query()
            ->where([
                'user_id' => user()->id,
                'id' => $id,
            ])
            ->fetchOne();

        if ($note === null) {
            return;
        }

        transaction($note, 'delete')->run();
        $this->invalidateCache();
    }

    /**
     * Create a new notification.
     *
     * @param Notification $note The notification to create.
     * @throws Throwable
     */
    public function create(Notification $note): void
    {
        $note->saveOrFail();
        $this->invalidateCache();
    }

    /**
     * Refresh the notifications cache.
     */
    public function refresh(): void
    {
        $this->invalidateCache();
        $this->all();
    }

    /**
     * Clear all notifications.
     * Note: this method can be optimized.
     * @throws Throwable
     */
    public function clear(): void
    {
        $items = $this->all();

        if (empty($items)) {
            return;
        }

        transaction($items, 'delete')->run();
        $this->invalidateCache();
    }

    /**
     * Count the number of unread notifications for the current user.
     *
     * @return int The number of unread notifications.
     */
    public function countUnread(): int
    {
        $cacheKey = 'user_' . user()->id . '_unread_notifications_count';
        if (cache()->has($cacheKey)) {
            return cache()->get($cacheKey);
        }

        $count = Notification::query()
            ->where([
                'user_id' => user()->id,
                'viewed' => false,
            ])
            ->count();

        cache()->set($cacheKey, $count, 60);

        return $count;
    }

    /**
     * Count the number of read notifications for the current user.
     *
     * @return int The number of read notifications.
     */
    public function countRead(): int
    {
        if ($this->cachedReadCount !== null) {
            return $this->cachedReadCount;
        }

        $this->cachedReadCount = Notification::query()
            ->where([
                'user_id' => user()->id,
                'viewed' => true,
            ])
            ->count();

        return $this->cachedReadCount;
    }

    /**
     * Count the total number of notifications for the current user.
     *
     * @return int The total number of notifications.
     */
    public function countAll(): int
    {
        if ($this->cachedTotalCount !== null) {
            return $this->cachedTotalCount;
        }

        $this->cachedTotalCount = Notification::query()
            ->where(['user_id' => user()->id])
            ->count();

        return $this->cachedTotalCount;
    }

    /**
     * Split a list of notifications by date.
     *
     * @param array $list List of notifications.
     *
     * @return array Notifications grouped by date.
     */
    protected function splitByDate(array $list): array
    {
        $result = [];

        foreach ($list as $item) {
            // for example: 1 week ago, yesterday and etc.
            $date = \Carbon\Carbon::parse($item->createdAt)->diffForHumans();

            unset($item->user);

            if (!isset($result[$date])) {
                $result[$date] = [];
            }

            $result[$date][] = $item;
        }

        return $result;
    }

    /**
     * Invalidate the notifications cache.
     */
    protected function invalidateCache(): void
    {
        $cacheKey = 'user_' . user()->id . '_unread_notifications_count';
        cache()->delete($cacheKey);
        $this->cached = false;
        $this->cachedItems = [];
        $this->unreadCached = false;
        $this->cachedUnreadItems = [];
        $this->cachedUnreadCount = null;
        $this->cachedReadCount = null;
        $this->cachedTotalCount = null;
        $this->readCached = false;
        $this->cachedReadItems = [];
    }
}
