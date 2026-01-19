<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Domain\Enums\OrderStatus;
use DateTimeImmutable;
use InvalidArgumentException;

final class Order
{
    private OrderStatus $status;
    private ?DateTimeImmutable $paidAt = null;

    public function __construct(
        public readonly string $id,
        public readonly int $amount,
        public readonly string $currency,
        public readonly string $customerEmail,
        OrderStatus $status = OrderStatus::Pending,
        public readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
    ) {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Order amount must be positive');
        }

        $this->status = $status;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function getPaidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function markAsPaid(DateTimeImmutable $paidAt = new DateTimeImmutable()): void
    {
        if (!$this->status->canBePaid()) {
            throw new InvalidArgumentException(
                "Cannot pay order with status: {$this->status->value}"
            );
        }

        $this->status = OrderStatus::Paid;
        $this->paidAt = $paidAt;
    }

    public function markAsRefunded(): void
    {
        if (!$this->status->canBeRefunded()) {
            throw new InvalidArgumentException(
                "Cannot refund order with status: {$this->status->value}"
            );
        }

        $this->status = OrderStatus::Refunded;
    }

    public function cancel(): void
    {
        if ($this->status !== OrderStatus::Pending) {
            throw new InvalidArgumentException(
                "Cannot cancel order with status: {$this->status->value}"
            );
        }

        $this->status = OrderStatus::Cancelled;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'customer_email' => $this->customerEmail,
            'status' => $this->status->value,
            'paid_at' => $this->paidAt?->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
