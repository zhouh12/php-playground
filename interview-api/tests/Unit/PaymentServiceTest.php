<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application\Contracts\IdGeneratorInterface;
use App\Application\Contracts\PaymentGatewayInterface;
use App\Application\Contracts\PaymentResultInterface;
use App\Application\Services\PaymentService;
use App\Domain\Contracts\OrderRepositoryInterface;
use App\Domain\Contracts\PaymentRepositoryInterface;
use App\Domain\Enums\OrderStatus;
use App\Domain\Enums\PaymentStatus;
use App\Domain\Exceptions\OrderNotFoundException;
use App\Domain\Exceptions\PaymentException;
use App\Domain\Models\Order;
use App\Domain\Models\Payment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the PaymentService.
 * 
 * These tests verify the service logic in isolation using mocks.
 */
final class PaymentServiceTest extends TestCase
{
    private PaymentService $paymentService;
    private MockObject&OrderRepositoryInterface $orderRepository;
    private MockObject&PaymentRepositoryInterface $paymentRepository;
    private MockObject&PaymentGatewayInterface $paymentGateway;
    private MockObject&IdGeneratorInterface $idGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $this->paymentGateway = $this->createMock(PaymentGatewayInterface::class);
        $this->idGenerator = $this->createMock(IdGeneratorInterface::class);

        $this->paymentService = new PaymentService(
            $this->orderRepository,
            $this->paymentRepository,
            $this->paymentGateway,
            $this->idGenerator,
        );
    }

    /**
     * @test
     */
    public function it_throws_exception_when_order_not_found(): void
    {
        // Arrange
        $this->orderRepository
            ->method('find')
            ->with('non-existent')
            ->willReturn(null);

        // Assert
        $this->expectException(OrderNotFoundException::class);
        $this->expectExceptionMessage('Order not found: non-existent');

        // Act
        $this->paymentService->processPayment('non-existent');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_order_already_paid(): void
    {
        // Arrange
        $order = $this->createPaidOrder('order-123');

        $this->orderRepository
            ->method('find')
            ->with('order-123')
            ->willReturn($order);

        // Assert
        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('already been paid');

        // Act
        $this->paymentService->processPayment('order-123');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_order_is_cancelled(): void
    {
        // Arrange
        $order = new Order(
            id: 'order-cancelled',
            amount: 1000,
            currency: 'USD',
            customerEmail: 'test@test.com',
            status: OrderStatus::Cancelled,
        );

        $this->orderRepository
            ->method('find')
            ->with('order-cancelled')
            ->willReturn($order);

        // Assert
        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('cannot be paid');

        // Act
        $this->paymentService->processPayment('order-cancelled');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_currency_not_supported(): void
    {
        // Arrange
        $order = new Order(
            id: 'order-xyz',
            amount: 1000,
            currency: 'XYZ',
            customerEmail: 'test@test.com',
        );

        $this->orderRepository
            ->method('find')
            ->willReturn($order);

        $this->paymentGateway
            ->method('supportsCurrency')
            ->with('XYZ')
            ->willReturn(false);

        $this->paymentGateway
            ->method('getName')
            ->willReturn('test-gateway');

        // Assert
        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('Currency XYZ is not supported');

        // Act
        $this->paymentService->processPayment('order-xyz');
    }

    /**
     * @test
     */
    public function it_processes_payment_successfully(): void
    {
        // Arrange
        $order = new Order(
            id: 'order-success',
            amount: 5000,
            currency: 'USD',
            customerEmail: 'customer@example.com',
        );

        $paymentResult = $this->createMock(PaymentResultInterface::class);
        $paymentResult->method('isSuccessful')->willReturn(true);
        $paymentResult->method('getTransactionId')->willReturn('txn_123');

        $this->orderRepository
            ->method('find')
            ->with('order-success')
            ->willReturn($order);

        $this->paymentGateway
            ->method('supportsCurrency')
            ->with('USD')
            ->willReturn(true);

        $this->paymentGateway
            ->method('charge')
            ->with($order)
            ->willReturn($paymentResult);

        $this->paymentGateway
            ->method('getName')
            ->willReturn('test-gateway');

        $this->idGenerator
            ->method('generate')
            ->willReturn('payment-uuid-123');

        // Expect payment to be saved
        $this->paymentRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Payment $payment) {
                return $payment->id === 'payment-uuid-123'
                    && $payment->orderId === 'order-success'
                    && $payment->amount === 5000
                    && $payment->currency === 'USD'
                    && $payment->status === PaymentStatus::Completed
                    && $payment->gatewayTransactionId === 'txn_123'
                    && $payment->gatewayName === 'test-gateway';
            }));

        // Expect order to be saved
        $this->orderRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Order $order) {
                return $order->getStatus() === OrderStatus::Paid
                    && $order->getPaidAt() !== null;
            }));

        // Act
        $response = $this->paymentService->processPayment('order-success');

        // Assert
        $this->assertTrue($response->success);
        $this->assertSame('Payment processed successfully', $response->message);
        $this->assertSame('order-success', $response->order['id']);
        $this->assertSame('paid', $response->order['status']);
        $this->assertSame('payment-uuid-123', $response->payment['id']);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_gateway_fails(): void
    {
        // Arrange
        $order = new Order(
            id: 'order-fail',
            amount: 5000,
            currency: 'USD',
            customerEmail: 'customer@example.com',
        );

        $paymentResult = $this->createMock(PaymentResultInterface::class);
        $paymentResult->method('isSuccessful')->willReturn(false);
        $paymentResult->method('getTransactionId')->willReturn('txn_failed');
        $paymentResult->method('getFailureReason')->willReturn('Insufficient funds');

        $this->orderRepository
            ->method('find')
            ->willReturn($order);

        $this->paymentGateway
            ->method('supportsCurrency')
            ->willReturn(true);

        $this->paymentGateway
            ->method('charge')
            ->willReturn($paymentResult);

        $this->paymentGateway
            ->method('getName')
            ->willReturn('test-gateway');

        $this->idGenerator
            ->method('generate')
            ->willReturn('payment-failed-uuid');

        // Failed payment should still be saved
        $this->paymentRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Payment $payment) {
                return $payment->status === PaymentStatus::Failed
                    && $payment->failureReason === 'Insufficient funds';
            }));

        // Order should NOT be saved (status unchanged)
        $this->orderRepository
            ->expects($this->never())
            ->method('save');

        // Assert
        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('Insufficient funds');

        // Act
        $this->paymentService->processPayment('order-fail');
    }

    /**
     * @test
     */
    public function it_calls_dependencies_in_correct_order(): void
    {
        // This test verifies the orchestration flow
        $order = new Order(
            id: 'order-flow',
            amount: 1000,
            currency: 'USD',
            customerEmail: 'test@test.com',
        );

        $paymentResult = $this->createMock(PaymentResultInterface::class);
        $paymentResult->method('isSuccessful')->willReturn(true);
        $paymentResult->method('getTransactionId')->willReturn('txn_flow');

        $callOrder = [];

        $this->orderRepository
            ->method('find')
            ->willReturnCallback(function () use (&$callOrder, $order) {
                $callOrder[] = 'find_order';
                return $order;
            });

        $this->paymentGateway
            ->method('supportsCurrency')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'check_currency';
                return true;
            });

        $this->paymentGateway
            ->method('charge')
            ->willReturnCallback(function () use (&$callOrder, $paymentResult) {
                $callOrder[] = 'charge';
                return $paymentResult;
            });

        $this->paymentGateway
            ->method('getName')
            ->willReturn('test');

        $this->idGenerator
            ->method('generate')
            ->willReturn('uuid');

        $this->paymentRepository
            ->method('save')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'save_payment';
            });

        $this->orderRepository
            ->method('save')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'save_order';
            });

        // Act
        $this->paymentService->processPayment('order-flow');

        // Assert - verify correct orchestration
        $this->assertSame([
            'find_order',
            'check_currency',
            'charge',
            'save_payment',
            'save_order',
        ], $callOrder);
    }

    /**
     * Helper to create a paid order for testing.
     */
    private function createPaidOrder(string $id): Order
    {
        $order = new Order(
            id: $id,
            amount: 1000,
            currency: 'USD',
            customerEmail: 'test@test.com',
        );
        $order->markAsPaid();
        return $order;
    }
}
