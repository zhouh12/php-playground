<?php

declare(strict_types=1);

namespace App\Infrastructure\Payment;

use App\Application\Contracts\PaymentResultInterface;

final readonly class PaymentResult implements PaymentResultInterface
{
    private function __construct(
        private bool $successful,
        private string $transactionId,
        private ?string $failureReason = null,
    ) {
    }

    public static function success(string $transactionId): self
    {
        return new self(
            successful: true,
            transactionId: $transactionId,
        );
    }

    public static function failure(string $transactionId, string $reason): self
    {
        return new self(
            successful: false,
            transactionId: $transactionId,
            failureReason: $reason,
        );
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }
}
