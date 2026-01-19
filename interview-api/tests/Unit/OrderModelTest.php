<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Enums\OrderStatus;
use App\Domain\Models\Order;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Order model.
 */
final class OrderModelTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_order_with_default_pending_status(): void
    {
        $order = new Order(
            id: 'order-1',
            amount: 1000,
            currency: 'USD',
            customerEmail: 'test@example.com',
        );

        $this->assertSame(OrderStatus::Pending, $order->getStatus());
        $this->assertNull($order->getPaidAt());
    }

    /**
     * @test
     */
    public function it_throws_exception_for_non_positive_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('amount must be positive');

        new Order(
            id: 'order-1',
            amount: 0,
            currency: 'USD',
            customerEmail: 'test@example.com',
        );
    }

    /**
     * @test
     */
    public function it_throws_exception_for_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Order(
            id: 'order-1',
            amount: -100,
            currency: 'USD',
            customerEmail: 'test@example.com',
        );
    }

    /**
     * @test
     */
    public function it_can_be_marked_as_paid(): void
    {
        $order = new Order(
            id: 'order-1',
            amount: 1000,
            currency: 'USD',
            customerEmail: 'test@example.com',
        );

        $paidAt = new DateTimeImmutable('2024-01-15 10:30:00');
        $order->markAsPaid($paidAt);

        $this->assertSame(OrderStatus::Paid, $order->getStatus());
        $this->assertSame($paidAt, $order->getPaidAt());
    }

    /**
     * @test
     */
    public function it_cannot_be_paid_twice(): void
    {
        $order = new Order(
            id: 'order-1',
            amount: 1000,
            currency: 'USD',
            customerEmail: 'test@example.com',
        );

        $order->markAsPaid();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot pay order with status: paid');

        $order->markAsPaid();
    }

    /**
     * @test
     */
    public function it_cannot_be_paid_when_cancelled(): void
    {
        $order = new Order(
            id: 'order-1',
            amount: 1000,
            currency: 'USD',
            customerEmail: 'test@example.com',
        );

        $order->cancel();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot pay order with status: cancelled');

        $order->markAsPaid();
    }

    /**
     * @test
     */
    public function it_can_be_cancelled_when_pending(): void
    {
        $order = new Order(
            id: 'order-1',
            amount: 1000,
            currency: 'USD',
            customerEmail: 'test@example.com',
        );

        $order->cancel();

        $this->assertSame(OrderStatus::Cancelled, $order->getStatus());
    }

    /**
     * @test
     */
    public function it_cannot_be_cancelled_when_paid(): void
    {
        $order = new Order(
            id: 'order-1',
            amount: 1000,
            currency: 'USD',
            customerEmail: 'test@example.com',
        );

        $order->markAsPaid();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot cancel order with status: paid');

        $order->cancel();
    }

    /**
     * @test
     */
    public function it_can_be_refunded_when_paid(): void
    {
        $order = new Order(
            id: 'order-1',
            amount: 1000,
            currency: 'USD',
            customerEmail: 'test@example.com',
        );

        $order->markAsPaid();
        $order->markAsRefunded();

        $this->assertSame(OrderStatus::Refunded, $order->getStatus());
    }

    /**
     * @test
     */
    public function it_cannot_be_refunded_when_pending(): void
    {
        $order = new Order(
            id: 'order-1',
            amount: 1000,
            currency: 'USD',
            customerEmail: 'test@example.com',
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot refund order with status: pending');

        $order->markAsRefunded();
    }

    /**
     * @test
     */
    public function it_serializes_to_array(): void
    {
        $createdAt = new DateTimeImmutable('2024-01-15 09:00:00');
        $order = new Order(
            id: 'order-array',
            amount: 2500,
            currency: 'EUR',
            customerEmail: 'array@test.com',
            createdAt: $createdAt,
        );

        $array = $order->toArray();

        $this->assertSame('order-array', $array['id']);
        $this->assertSame(2500, $array['amount']);
        $this->assertSame('EUR', $array['currency']);
        $this->assertSame('array@test.com', $array['customer_email']);
        $this->assertSame('pending', $array['status']);
        $this->assertNull($array['paid_at']);
        $this->assertSame('2024-01-15 09:00:00', $array['created_at']);
    }
}
