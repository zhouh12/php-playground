<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Domain\Enums\PaymentStatus;
use DateTimeImmutable;
use InvalidArgumentException;

final class Payment
{
    public function __construct(
        public readonly string $id,
        public readonly string $orderId,
        public readonly int $amount,
        public readonly string $currency,
        public readonly PaymentStatus $status,
        public readonly string $gatewayTransactionId,
        public readonly string $gatewayName,
        public readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
        public readonly ?string $failureReason = null,
    ) {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be positive');
        }
    }

    public function isSuccessful(): bool
    {
        return $this->status->isSuccessful();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->orderId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status->value,
            'gateway_transaction_id' => $this->gatewayTransactionId,
            'gateway_name' => $this->gatewayName,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'failure_reason' => $this->failureReason,
        ];
    }

    public static function successful(
        string $id,
        string $orderId,
        int $amount,
        string $currency,
        string $gatewayTransactionId,
        string $gatewayName,
    ): self {
        return new self(
            id: $id,
            orderId: $orderId,
            amount: $amount,
            currency: $currency,
            status: PaymentStatus::Completed,
            gatewayTransactionId: $gatewayTransactionId,
            gatewayName: $gatewayName,
        );
    }

    public static function failed(
        string $id,
        string $orderId,
        int $amount,
        string $currency,
        string $gatewayTransactionId,
        string $gatewayName,
        string $failureReason,
    ): self {
        return new self(
            id: $id,
            orderId: $orderId,
            amount: $amount,
            currency: $currency,
            status: PaymentStatus::Failed,
            gatewayTransactionId: $gatewayTransactionId,
            gatewayName: $gatewayName,
            failureReason: $failureReason,
        );
    }
}
