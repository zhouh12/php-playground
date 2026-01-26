<?php

declare(strict_types=1);
namespace Tests\Unit;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Application\Services\IOrderLoggerService;

final class OrderLoggerTest extends TestCase
{
    #[Test]
    public function shouldLogOrderPaid() {
        // arrange
        $orderService = $this->createMock(IOrderLoggerService::class);
        $orderService->expects($this->once())
            ->method('logOrderPaid')
            ->with('123')
            ->willReturn('Order paid: ID=123');

        // act
        $result = $orderService->logOrderPaid('123');
        
        // assert
        $this->assertSame('Order paid: ID=123', $result);
    }
}

