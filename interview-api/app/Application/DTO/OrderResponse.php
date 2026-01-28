<?php

namespace App\Application\DTO;

use App\Domain\Enums\OrderStatus;

final class OrderResponse
{
    public function __construct(
        public string $orderId,
        public OrderStatus $status,
        public float $originalAmount,
        public int $discount,
        public float $finalAmount,
        public ?string $error
        ){}
}