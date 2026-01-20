<?php

namespace App\Http\Controllers;

use App\Application\Services\IUserService;
use App\Http\JsonResponse;

final readonly class UserController {
    public function __construct(
        private IUserService $userService
    ){}
     

    public function findUser(array $parameter): array {
        $email = $parameter["email"];
        $user = $this->userService->getUserByEmail($email);
        
    }
}