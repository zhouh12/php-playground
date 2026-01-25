<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Request;
use App\Http\JsonResponse;
use App\Application\DTO\UserResponse;
use App\Application\Services\IUserService;

final readonly class UserController {
    public function __construct(
        private IUserService $userService
    ){}
     

    /**
     * GET api/users/{id}
     */
    public function user(Request $request, array $parameter): JsonResponse {
        $id = $parameter["userId"];
        $user = $this->userService->getUserById($id);
        return JsonResponse::success([
            'success' => true,
            'user' =>$user,
        ]);
    }
}