<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use Exception;

final class PaymentException extends Exception
{
    public static function orderAlreadyPaid(string $orderId): self
    {
        return new self("Order {$orderId} has already been paid");
    }

    public static function orderNotPayable(string $orderId, string $status): self
    {
        return new self("Order {$orderId} cannot be paid (status: {$status})");
    }

    public static function gatewayFailed(string $reason): self
    {
        return new self("Payment gateway error: {$reason}");
    }

    public static function currencyNotSupported(string $currency, string $gateway): self
    {
        return new self("Currency {$currency} is not supported by {$gateway} gateway");
    }
}
