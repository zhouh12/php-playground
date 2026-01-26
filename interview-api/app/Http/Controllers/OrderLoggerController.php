<?php

declare(strict_types=1);
use App\Application\Services\IOrderLoggerService;

final readonly class OrderLoggerController
{
    public function __construct(
        private IOrderLoggerService $orderLoggerService)
    {

    }

    public function logOrder() {
        $this->orderLoggerService->logOrderPaid('test');
    }
}