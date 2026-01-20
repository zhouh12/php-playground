<?php

namespace App\Application\DTO;

final readonly class UserResponse 
{
    public function __construct(
        public int $userId,
        public string $firstName,
        public string $lastName
    ){}
}