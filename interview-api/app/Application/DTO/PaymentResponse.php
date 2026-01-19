<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Models\Order;
use App\Domain\Models\Payment;

/**
 * Data Transfer Object for payment API responses.
 */
final readonly class PaymentResponse
{
    public function __construct(
        public bool $success,
        public string $message,
        public ?array $order = null,
        public ?array $payment = null,
    ) {
    }

    public static function success(Order $order, Payment $payment): self
    {
        return new self(
            success: true,
            message: 'Payment processed successfully',
            order: $order->toArray(),
            payment: $payment->toArray(),
        );
    }

    public static function failure(string $message): self
    {
        return new self(
            success: false,
            message: $message,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'success' => $this->success,
            'message' => $this->message,
            'order' => $this->order,
            'payment' => $this->payment,
        ], fn($value) => $value !== null);
    }
}
