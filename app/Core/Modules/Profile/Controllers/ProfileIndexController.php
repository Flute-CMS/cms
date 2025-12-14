<?php

namespace Flute\Core\Modules\Profile\Controllers;

use DateTime;
use DateTimeInterface;
use Flute\Core\Database\Entities\User;
use Flute\Core\Modules\Profile\Events\ProfileRenderEvent;
use Flute\Core\Modules\Profile\Events\ProfileSearchEvent;
use Flute\Core\Modules\Profile\Services\ProfileTabService;
use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;

class ProfileIndexController extends BaseController
{
    protected ?string $path;

    protected ?ProfileRenderEvent $event = null;

    /**
     * Initializes the path from the request input or defaults to '/'.
     */
    public function __construct()
    {
        $this->path = request()->input('tab') ?? '';
    }

    /**
     * Displays the profile page with the necessary data.
     *
     * @param string|int $id
     */
    public function index(FluteRequest $request, $id, ProfileTabService $profileTabService)
    {
        abort_if($this->path === '' || $profileTabService->getTabsByPath($this->path)->count() !== 0, 404);
        $stringId = (string) $id;
        $cacheKey = 'profile.search.resolve.' . sha1($stringId);
        $cachedTarget = cache()->get($cacheKey);

        if (is_string($cachedTarget) && $cachedTarget !== '') {
            $user = is_numeric($cachedTarget) ? user()->get((int) $cachedTarget) : user()->getByRoute($cachedTarget);
        } else {
            $searchEvent = events()->dispatch(new ProfileSearchEvent($id), ProfileSearchEvent::NAME);
            $candidate = $searchEvent->getUser();
            $user = ($candidate instanceof User && isset($candidate->id)) ? $candidate : $this->getUser($id);
        }

        if (!$user) {
            return $this->errors()->notFound();
        }

        $this->dispatchEvent($user);

        cache()->set($cacheKey, $user->getUrl(), 86400);

        if ($request->isOnlyHtmx() && $this->path !== '') {
            return response()->make($profileTabService->renderTabsByPath($this->path, $user));
        }

        breadcrumb()
            ->add(__('def.home'), url('/'))
            ->add(__('def.profile') . " - {$user->name}");

        $tabs = $profileTabService->getTabs();

        if (empty($this->path) && !empty($tabs) && $tabs->count() > 0) {
            $this->path = $tabs[0]['path'];
        }

        $initialTabHtml = '';

        if (!empty($this->path) && !empty($tabs)) {
            $initialTabHtml = $profileTabService->renderTabsByPath($this->path, $user);
        }

        return view('flute::pages.profile.index', [
            'id' => $user->id,
            'user' => $this->event->getUser(),
            'activePath' => $this->path,
            'tabs' => $tabs,
            'last_logged' => $this->getLastLoggedPhrase($this->event->getUser()->last_logged),
            'initialTabHtml' => $initialTabHtml,
        ]);
    }

    public function mini($id, ProfileTabService $profileTabService)
    {
        $stringId = (string) $id;
        $cacheKey = 'profile.search.resolve.' . sha1($stringId);
        $cachedTarget = cache()->get($cacheKey);

        if (is_string($cachedTarget) && $cachedTarget !== '') {
            $user = is_numeric($cachedTarget) ? user()->get((int) $cachedTarget) : user()->getByRoute($cachedTarget);
        } else {
            $searchEvent = events()->dispatch(new ProfileSearchEvent($id), ProfileSearchEvent::NAME);
            $candidate = $searchEvent->getUser();
            $user = ($candidate instanceof User && isset($candidate->id)) ? $candidate : $this->getUser($id);
        }

        if (!$user) {
            return $this->errors()->notFound();
        }

        if ($user->hidden && !(user()->isLoggedIn() && (user()->id === $user->id || user()->can('admin.users')))) {
            return $this->errors()->forbidden(__('profile.profile_hidden'));
        }

        $this->dispatchEvent($user, 'mini');

        cache()->set($cacheKey, $user->getUrl(), 86400);

        return view('flute::partials.user-card', [
            'id' => $user->id,
            'user' => $this->event->getUser(),
        ]);
    }

    /**
     * Retrieves a User based on the provided ID.
     *
     * @param string|int $id
     */
    protected function getUser($id): ?User
    {
        return is_numeric($id) ? user()->get($id) : user()->getByRoute($id);
    }

    /**
     * Dispatches the ProfileRenderEvent.
     */
    protected function dispatchEvent(User $user, string $type = 'full'): void
    {
        $event = new ProfileRenderEvent($user, $this->path, $type);

        $this->event = events()->dispatch($event, ProfileRenderEvent::NAME);
    }

    /**
     * Formats the last logged-in phrase.
     *
     * @param DateTimeInterface $lastLoggedDateTime
     */
    private function getLastLoggedPhrase($lastLoggedDateTime): string
    {
        if (!$lastLoggedDateTime instanceof DateTimeInterface) {
            return __('def.not_online');
        }

        $now = new DateTime();
        $interval = $now->getTimestamp() - $lastLoggedDateTime->getTimestamp();

        if ($interval <= 600) {
            return __('def.online');
        }

        if ($lastLoggedDateTime->getTimestamp() > 0) {
            return \Carbon\Carbon::parse($lastLoggedDateTime)->diffForHumans();
        }

        return __('def.not_online');
    }
}
