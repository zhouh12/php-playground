<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\Models\Payment;

/**
 * Interface for payment persistence operations.
 */
interface PaymentRepositoryInterface
{
    /**
     * Find a payment by its ID.
     *
     * @param string $id The payment ID
     * @return Payment|null The payment if found, null otherwise
     */
    public function find(string $id): ?Payment;

    /**
     * Find all payments for a given order.
     *
     * @param string $orderId The order ID
     * @return Payment[]
     */
    public function findByOrderId(string $orderId): array;

    /**
     * Save a payment.
     *
     * @param Payment $payment The payment to save
     * @return void
     */
    public function save(Payment $payment): void;
}
