<?php

declare(strict_types=1);

class OrderLogger
{
    private $logFile;
    
    public function __construct()
    {
        $this->logFile = __DIR__ . '/../../logs/orders.log';
    }
    
    public function logOrderCreated($orderId, $amount)
    {
        $timestamp = date('Y-m-d H:i:s');
        $message = "[$timestamp] Order created: ID=$orderId, Amount=$amount\n";
        echo $message;
        file_put_contents($this->logFile, $message, FILE_APPEND);
    }
    
    public function logOrderPaid($orderId)
    {
        $timestamp = date('Y-m-d H:i:s');
        $message = "[$timestamp] Order paid: ID=$orderId\n";
        echo $message;
        file_put_contents($this->logFile, $message, FILE_APPEND);
    }
    
    public function logOrderFailed($orderId, $reason)
    {
        $timestamp = date('Y-m-d H:i:s');
        $message = "[$timestamp] Order failed: ID=$orderId, Reason=$reason\n";
        echo $message;
        file_put_contents($this->logFile, $message, FILE_APPEND);
    }
}