<?php
declare(strict_types=1);

namespace App\Application\Services;

use App\Application\DTO\UserResponse;

interface IUserService 
{
    public function getUserById(string $id): UserResponse;
}

final readonly class UserService implements IUserService
{
    public function getUserById(string $id): UserResponse
    {
        // db repo find user by email
        // $user = $this->dbRepo->findUserByEmail($email);
        $user = new UserResponse("guid", "jimmy", "zhou");
        return $user;
    }
}