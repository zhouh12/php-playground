<?php

declare(strict_types=1);

namespace App\Application\Services;

final readonly class OrderLoggerService implements IOrderLoggerService
{
    public function __construct(
        public string $logFile)
    {
        // $logFile = __DIR__ . '/../../logs/orders.log';
    }
    
    private function getTimeStamp(): string 
    {
        return date('Y-m-d H:i:s');
    }

    public function logOrderCreated(string $orderId, float $amount): string
    {
        
        $timestamp = $this->getTimeStamp();
        $message = "[$timestamp] Order created: ID=$orderId, Amount=$amount\n";

        return $message;
    }

    public function logOrderPaid(string $orderId): string
    {
        $timestamp = $this->getTimeStamp();
        $message = "[$timestamp] Order paid: ID=$orderId\n";
        file_put_contents($this->logFile, $message, FILE_APPEND);

        return $message;
    }

    public function logOrderFailed(string $orderId, string $reason): string
    {  
        $timestamp = $this->getTimeStamp();
        $message = "[$timestamp] Order failed: ID=$orderId, Reason=$reason\n";
        file_put_contents($this->logFile, $message, FILE_APPEND);
        
        return $message;
    }
}