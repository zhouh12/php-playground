<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use Exception;

final class OrderNotFoundException extends Exception
{
    public static function withId(string $id): self
    {
        return new self("Order not found: {$id}");
    }
}
