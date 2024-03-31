<?php

namespace Flute\Core\Http\Controllers\Profile;

use Flute\Core\Http\Middlewares\CSRFMiddleware;
use Flute\Core\Services\ProfileService;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class EditController extends AbstractController
{
    protected ?string $currentMode = 'main';

    public function __construct()
    {
        $this->currentMode = request()->input('mode', 'main');
        
        page()->disablePageEditor();

        $this->middleware(CSRFMiddleware::class);
    }

    public function index(FluteRequest $request, ProfileService $profileService)
    {
        abort_if($profileService->searchMode($this->currentMode), 404);

        // Retrieve user data
        $user = user()->getCurrentUser();

        // Add breadcrumbs
        breadcrumb()
            ->add(__('def.home'), url('/'))
            ->add(__('def.profile') . " - $user->name", url("profile/{$user->getUrl()}"))
            ->add(__('def.settings'));

        return view('pages/profile/edit.blade.php', [
            "id" => $user->id,
            "user" => $user,
            "active" => $this->currentMode,
            "mod_content" => $profileService->renderMode($this->currentMode, $user),
            "mods" => $profileService->getMods(),
        ], true);
    }

    // public function updatePassword( FluteRequest $request )
    // {

    // }

    // public function updateEmail( FluteRequest $request )
    // {

    // }

    public function updateUri(FluteRequest $request)
    {
        $value = $request->input('value');
        $strlen = mb_strlen($value);

        if( empty($value) )
            return $this->updateUser('uri', null);

        // Проверка на пустое значение и длину строки
        if ($strlen < 3 || $strlen > 50) {
            return $this->error(__('profile.uri_error'));
        }

        // Проверка на безопасные символы с помощью регулярного выражения
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $value)) {
            return $this->error(__('profile.uri_error'));
        }

        // Обновление URI пользователя
        return $this->updateUser('uri', $value);
    }

    public function updateName(FluteRequest $request)
    {
        $value = $request->input('value');
        $strlen = mb_strlen($value);

        // Проверка на пустое значение и длину строки
        if (empty($value) || $strlen < config('auth.validation.name.min_length') || $strlen > config('auth.validation.name.max_length')) {
            return $this->error(__('profile.name_error'));
        }

        if (!preg_match('/^[a-zA-Z0-9\s\p{L}\p{M},.;:\'"\[\]()\-]+$/u', $value)) {
            return $this->error(__('profile.name_error'));
        }

        // Обновление имени пользователя
        return $this->updateUser('name', $value);
    }

    public function updateHidden(FluteRequest $request)
    {
        return $this->updateUser("hidden", $request->input('value') === "true");
    }

    protected function updateUser(string $key, $value)
    {
        $user = user()->getCurrentUser();
        $user->$key = is_bool($value) ? $value : htmlspecialchars($value);
        transaction($user)->run();

        return $this->success();
    }
}