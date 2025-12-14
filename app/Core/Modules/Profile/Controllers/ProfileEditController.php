<?php

namespace Flute\Core\Modules\Profile\Controllers;

use Flute\Core\Modules\Profile\Services\ProfileEditTabService;
use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;

class ProfileEditController extends BaseController
{
    protected ?string $path;

    public function __construct()
    {
        $this->path = request()->input('tab', 'full');

        page()->disablePageEditor();
    }

    public function index(FluteRequest $request, ProfileEditTabService $profileTabService)
    {
        $user = user()->getCurrentUser();

        breadcrumb()
            ->add(__('def.home'), url('/'))
            ->add(__('def.profile') . " - {$user->name}", url("profile/{$user->getUrl()}"))
            ->add(__('def.settings'), url('profile/settings'));

        $tabs = $profileTabService->getTabs();

        if ($this->path === 'full') {
            return view('flute::pages.profile.edit-full', [
                "id" => $user->id,
                "user" => $user,
                'activePath' => $this->path,
                'tabs' => $tabs,
            ]);
        }

        abort_if($profileTabService->getTabsByPath($this->path)->count() !== 0, 404);

        $activeTab = $profileTabService->getTabsByPath($this->path);
        $activeTabContent = $profileTabService->renderTabsByPath($this->path, $user);

        return view('flute::pages.profile.edit-main', [
            "id" => $user->id,
            "user" => $user,
            'activePath' => $this->path,
            'tabs' => $tabs,
            'activeTab' => $activeTab,
            'activeTabContent' => $activeTabContent,
        ])->fragmentIf($request->isOnlyHtmx(), 'profile-edit-card');
    }
}
