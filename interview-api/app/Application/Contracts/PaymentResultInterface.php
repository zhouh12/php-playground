<?php

declare(strict_types=1);

namespace App\Application\Contracts;

/**
 * Represents the result of a payment gateway transaction.
 */
interface PaymentResultInterface
{
    /**
     * Whether the payment was successful.
     */
    public function isSuccessful(): bool;

    /**
     * Get the gateway's transaction ID.
     */
    public function getTransactionId(): string;

    /**
     * Get the failure reason if the payment failed.
     */
    public function getFailureReason(): ?string;
}
