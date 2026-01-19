<?php

declare(strict_types=1);

namespace App\Infrastructure\Payment;

use App\Application\Contracts\PaymentGatewayInterface;
use App\Application\Contracts\PaymentResultInterface;
use App\Domain\Models\Order;

/**
 * Stripe payment gateway implementation.
 * 
 * In a real application, this would integrate with Stripe's API.
 * For this interview example, it simulates successful payments.
 */
final readonly class StripePaymentGateway implements PaymentGatewayInterface
{
    private const array SUPPORTED_CURRENCIES = ['USD', 'EUR', 'GBP', 'CAD', 'AUD'];

    public function charge(Order $order): PaymentResultInterface
    {
        // In production, this would call Stripe's API
        // For demonstration, we simulate a successful charge
        
        // Simulate generating a Stripe charge ID
        $transactionId = 'ch_' . bin2hex(random_bytes(12));

        return PaymentResult::success($transactionId);
    }

    public function getName(): string
    {
        return 'stripe';
    }

    public function supportsCurrency(string $currency): bool
    {
        return in_array(strtoupper($currency), self::SUPPORTED_CURRENCIES, true);
    }
}
