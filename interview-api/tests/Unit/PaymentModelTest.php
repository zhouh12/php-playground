<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Enums\PaymentStatus;
use App\Domain\Models\Payment;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Payment model.
 */
final class PaymentModelTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_successful_payment(): void
    {
        $payment = Payment::successful(
            id: 'pay-1',
            orderId: 'order-1',
            amount: 5000,
            currency: 'USD',
            gatewayTransactionId: 'ch_123',
            gatewayName: 'stripe',
        );

        $this->assertSame(PaymentStatus::Completed, $payment->status);
        $this->assertTrue($payment->isSuccessful());
        $this->assertNull($payment->failureReason);
    }

    /**
     * @test
     */
    public function it_creates_failed_payment(): void
    {
        $payment = Payment::failed(
            id: 'pay-2',
            orderId: 'order-2',
            amount: 5000,
            currency: 'USD',
            gatewayTransactionId: 'ch_failed',
            gatewayName: 'stripe',
            failureReason: 'Card declined',
        );

        $this->assertSame(PaymentStatus::Failed, $payment->status);
        $this->assertFalse($payment->isSuccessful());
        $this->assertSame('Card declined', $payment->failureReason);
    }

    /**
     * @test
     */
    public function it_throws_exception_for_non_positive_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('amount must be positive');

        new Payment(
            id: 'pay-invalid',
            orderId: 'order-1',
            amount: 0,
            currency: 'USD',
            status: PaymentStatus::Pending,
            gatewayTransactionId: 'txn_123',
            gatewayName: 'stripe',
        );
    }

    /**
     * @test
     */
    public function it_serializes_to_array(): void
    {
        $createdAt = new DateTimeImmutable('2024-01-15 11:00:00');
        $payment = new Payment(
            id: 'pay-array',
            orderId: 'order-array',
            amount: 3500,
            currency: 'GBP',
            status: PaymentStatus::Completed,
            gatewayTransactionId: 'ch_array',
            gatewayName: 'stripe',
            createdAt: $createdAt,
        );

        $array = $payment->toArray();

        $this->assertSame('pay-array', $array['id']);
        $this->assertSame('order-array', $array['order_id']);
        $this->assertSame(3500, $array['amount']);
        $this->assertSame('GBP', $array['currency']);
        $this->assertSame('completed', $array['status']);
        $this->assertSame('ch_array', $array['gateway_transaction_id']);
        $this->assertSame('stripe', $array['gateway_name']);
        $this->assertSame('2024-01-15 11:00:00', $array['created_at']);
        $this->assertNull($array['failure_reason']);
    }

    /**
     * @test
     */
    public function it_includes_failure_reason_in_array_when_present(): void
    {
        $payment = Payment::failed(
            id: 'pay-fail',
            orderId: 'order-fail',
            amount: 1000,
            currency: 'USD',
            gatewayTransactionId: 'ch_fail',
            gatewayName: 'stripe',
            failureReason: 'Insufficient funds',
        );

        $array = $payment->toArray();

        $this->assertSame('Insufficient funds', $array['failure_reason']);
    }
}
