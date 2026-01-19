<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function isPaid(): bool
    {
        return $this === self::Paid;
    }

    public function canBePaid(): bool
    {
        return $this === self::Pending;
    }

    public function canBeRefunded(): bool
    {
        return $this === self::Paid;
    }
}
