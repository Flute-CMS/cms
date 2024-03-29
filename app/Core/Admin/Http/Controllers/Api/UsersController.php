<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Http\Middlewares\CSRFMiddleware;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;
use Flute\Core\Admin\Services\UserService;

class UsersController extends AbstractController
{
    private $userService;

    // Constructor with UserService injection
    public function __construct(UserService $userService)
    {
        HasPermissionMiddleware::permission('admin.users');
        $this->middleware(HasPermissionMiddleware::class);
        $this->middleware(CSRFMiddleware::class);
        $this->userService = $userService;
    }

    /**
     * Edit user details.
     *
     * @param FluteRequest $request
     * @param string $id User ID
     * @return Response
     */
    public function edit(FluteRequest $request, string $id): Response
    {
        $result = $this->userService->editUser($request->input(), $id);

        if ($result['status'] === 'error') {
            return $this->error($result['message'], $result['code']);
        }

        return $this->success();
    }

    public function delete(FluteRequest $request, string $id): Response
    {
        $result = $this->userService->deleteUser((int) $id, user()->getCurrentUser());

        if ($result['status'] === 'error') {
            return $this->error($result['message'], $result['code']);
        }

        return $this->success();
    }
}
