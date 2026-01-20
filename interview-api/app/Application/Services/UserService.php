<?php
declare(strict_types=1);

namespace App\Application\Services;

use App\Application\DTO\UserResponse;

interface IUserService 
{
    public function getUserByEmail(string $email): UserResponse;
}

final readonly class UserService implements IUserService
{
    public function getUserByEmail(string $email): UserResponse
    {
        // db repo find user by email
        // $user = $this->dbRepo->findUserByEmail($email);
        $user = new UserResponse(123, "jimmy", "zhou");
        return $user;
    }
}