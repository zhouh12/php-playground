<?php

declare(strict_types=1);

namespace App\Application\Contracts;

use App\Domain\Models\Order;

/**
 * Interface for payment gateway implementations.
 * 
 * This interface belongs in the Application layer as it defines
 * how use cases interact with external payment services.
 * Implementing this interface allows new payment gateways to be added
 * without modifying existing code (Open-Closed Principle).
 */
interface PaymentGatewayInterface
{
    /**
     * Process a payment for the given order.
     *
     * @param Order $order The order to process payment for
     * @return PaymentResultInterface The result of the payment attempt
     */
    public function charge(Order $order): PaymentResultInterface;

    /**
     * Get the name of this payment gateway.
     *
     * @return string Gateway identifier (e.g., 'stripe', 'paypal')
     */
    public function getName(): string;

    /**
     * Check if the gateway supports the given currency.
     *
     * @param string $currency ISO 4217 currency code
     * @return bool
     */
    public function supportsCurrency(string $currency): bool;
}
