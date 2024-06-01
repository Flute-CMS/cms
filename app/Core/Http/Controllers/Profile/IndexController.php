<?php

namespace Flute\Core\Http\Controllers\Profile;

use Flute\Core\Support\AbstractController;
use Flute\Core\Database\Entities\User;
use Flute\Core\Events\ProfileRenderEvent;
use Flute\Core\Services\ProfileService;
use Flute\Core\Support\FluteRequest;

class IndexController extends AbstractController
{
    protected ?string $tab;
    protected ?ProfileRenderEvent $event = null;

    /**
     * ProfileController constructor.
     * Initialize the tab to the request input or default to 'main'
     */
    public function __construct()
    {
        $this->tab = request()->input('tab') ?? 'main';

        // page()->disablePageEditor();
    }

    /**
     * Index method for ProfileController
     * It provides the view for the profile page with necessary data
     * 
     * @param FluteRequest $request
     * @param string|int $id
     * @param ProfileService $profileService
     * 
     * @return mixed
     */
    public function index(FluteRequest $request, $id, ProfileService $profileService)
    {
        // Retrieve user data
        $user = $this->u($id);

        $this->e($user);

        // Add breadcrumbs
        breadcrumb()
            ->add(__('def.home'), url('/'))
            ->add(__('def.profile') . " - $user->name");

        try {
            $tab = $profileService->renderTab($this->event->getActiveTab(), $user);
        } catch (\RuntimeException $e) {
            // logs()->warning($e);
            $tab = '';
        }

        // Return profile view with necessary data
        return view('pages/profile/index.blade.php', [
            "id" => $user->id,
            "user" => $this->event->getUser(),
            "active" => $this->event->getActiveTab(),
            "tab_content" => $tab,
            "tabs" => $profileService->getTabs(),
            "last_logged" => $this->getLastLoggedPhrase($this->event->getUser()->last_logged)
        ], true);
    }

    private function getLastLoggedPhrase($lastLoggedDateTime): string
    {
        $now = new \DateTime();
        $interval = $now->getTimestamp() - $lastLoggedDateTime->getTimestamp();

        if ($interval <= 600) {
            return __('def.online');
        } elseif ($interval <= 3600) {
            return __('def.was_in_hour');
        } else {
            return __('def.was_in_online', [
                ':time' => $lastLoggedDateTime->format(default_date_format())
            ]);
        }
    }

    /**
     * Retrieve User based on id
     * @param $id
     * @return User
     */
    protected function u($id): User
    {
        return is_numeric($id) ? user()->get($id) : user()->getByRoute($id);
    }

    /**
     * Dispatch the ProfileRenderEvent event
     * @param User $user
     */
    protected function e(User $user): void
    {
        $event = new ProfileRenderEvent($user, $this->tab);

        $this->event = events()->dispatch($event, ProfileRenderEvent::NAME);
    }
}