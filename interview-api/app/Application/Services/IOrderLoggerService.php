<?php

declare(strict_types=1);

namespace App\Application\Services;


interface IOrderLoggerService 
{
    public function logOrderCreated(string $orderId, float $amount): string;
    public function logOrderPaid(string $orderId): string;
    public function logOrderFailed(string $orderId, string $reason): string;
}