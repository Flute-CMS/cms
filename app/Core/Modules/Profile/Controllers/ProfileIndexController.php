<?php

namespace Flute\Core\Modules\Profile\Controllers;

use Flute\Core\Modules\Profile\Events\ProfileRenderEvent;
use Flute\Core\Modules\Profile\Events\ProfileSearchEvent;
use Flute\Core\Modules\Profile\Services\ProfileTabService;
use Flute\Core\Support\BaseController;
use Flute\Core\Database\Entities\User;
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
        $this->path = request()->input('tab', '');
    }

    /**
     * Displays the profile page with the necessary data.
     *
     * @param FluteRequest $request
     * @param string|int $id
     * @param ProfileTabService $profileTabService
     */
    public function index(FluteRequest $request, $id, ProfileTabService $profileTabService)
    {
        abort_if($this->path === '' || $profileTabService->getTabsByPath($this->path)->count() !== 0, 404);

        $searchEvent = events()->dispatch(new ProfileSearchEvent($id), ProfileSearchEvent::NAME);
        $user = $searchEvent->getUser() ?? $this->getUser($id);

        if (!$user) {
            return $this->errors()->notFound();
        }

        $this->dispatchEvent($user);

        if ($request->isOnlyHtmx()) {
            return response()->make($profileTabService->renderTabsByPath($this->path, $user));
        }

        breadcrumb()
            ->add(__('def.home'), url('/'))
            ->add(__('def.profile') . " - {$user->name}");

        $tabs = $profileTabService->getTabs();

        if ($this->path === '' && !empty($tabs)) {
            $this->path = $tabs[0]['path'];
        }

        return view('flute::pages.profile.index', [
            'id' => $user->id,
            'user' => $this->event->getUser(),
            'activePath' => $this->path,
            'tabs' => $tabs,
            'last_logged' => $this->getLastLoggedPhrase($this->event->getUser()->last_logged),
        ]);
    }

    public function mini($id, ProfileTabService $profileTabService)
    {
        $searchEvent = events()->dispatch(new ProfileSearchEvent($id), ProfileSearchEvent::NAME);
        $user = $searchEvent->getUser() ?? $this->getUser($id);

        if (!$user) {
            return $this->errors()->notFound();
        }

        $this->dispatchEvent($user, 'mini');

        return view('flute::partials.user-card', [
            'id' => $user->id,
            'user' => $this->event->getUser(),
        ]);
    }

    /**
     * Formats the last logged-in phrase.
     *
     * @param \DateTimeInterface $lastLoggedDateTime
     * @return string
     */
    private function getLastLoggedPhrase($lastLoggedDateTime): string
    {
        $now = new \DateTime();
        $interval = $now->getTimestamp() - $lastLoggedDateTime->getTimestamp();

        if ($interval <= 600) {
            return __('def.online');
        } elseif($lastLoggedDateTime->getTimestamp() > 0) {
            return \Carbon\Carbon::parse($lastLoggedDateTime)->diffForHumans();
        } else {
            return __('def.not_online');
        }
    }

    /**
     * Retrieves a User based on the provided ID.
     *
     * @param string|int $id
     * @return User|null
     */
    protected function getUser($id): ?User
    {
        return is_numeric($id) ? user()->get($id) : user()->getByRoute($id);
    }

    /**
     * Dispatches the ProfileRenderEvent.
     *
     * @param User $user
     */
    protected function dispatchEvent(User $user, string $type = 'full'): void
    {
        $event = new ProfileRenderEvent($user, $this->path, $type);

        $this->event = events()->dispatch($event, ProfileRenderEvent::NAME);
    }
}
