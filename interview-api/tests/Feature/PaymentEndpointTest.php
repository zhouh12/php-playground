<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Request;
use App\Domain\Enums\OrderStatus;
use App\Domain\Enums\PaymentStatus;
use PHPUnit\Framework\Attributes\Test;
use App\Domain\Contracts\OrderRepositoryInterface;
use App\Domain\Contracts\PaymentRepositoryInterface;

/**
 * Feature tests for the payment endpoint.
 * 
 * These tests lock in the current behavior of POST /api/orders/{orderId}/pay
 * and serve as a safety net before adding new features.
 */
final class PaymentEndpointTest extends TestCase
{
    #[Test]
    public function it_successfully_processes_payment_for_pending_order(): void
    {
        // Arrange
        $order = $this->createOrder(
            id: 'order-001',
            amount: 5000,
            currency: 'USD',
            customerEmail: 'customer@example.com',
        );

        // Act
        $request = new Request(
            method: 'POST',
            uri: '/api/orders/order-001/pay',
        );
        $response = $this->app->getRouter()->dispatch($request);

        // Assert
        $this->assertSame(200, $response->statusCode);
        $this->assertTrue($response->data['success']);
        $this->assertSame('Payment processed successfully', $response->data['message']);

        // Verify order was updated
        $this->assertSame('paid', $response->data['order']['status']);
        $this->assertNotNull($response->data['order']['paid_at']);

        // Verify payment was created
        $this->assertSame('completed', $response->data['payment']['status']);
        $this->assertSame(5000, $response->data['payment']['amount']);
        $this->assertSame('USD', $response->data['payment']['currency']);
        $this->assertSame('stripe', $response->data['payment']['gateway_name']);
        $this->assertStringStartsWith('ch_', $response->data['payment']['gateway_transaction_id']);
    }

    #[Test]
    public function it_returns_404_for_non_existent_order(): void
    {
        // Act
        $request = new Request(
            method: 'POST',
            uri: '/api/orders/non-existent-order/pay',
        );
        $response = $this->app->getRouter()->dispatch($request);

        // Assert
        $this->assertSame(404, $response->statusCode);
        $this->assertFalse($response->data['success']);
        $this->assertStringContainsString('not found', $response->data['error']);
    }

    #[Test]
    public function it_returns_422_when_order_is_already_paid(): void
    {
        // Arrange
        $order = $this->createOrder(id: 'order-paid', status: OrderStatus::Pending);
        
        // Pay the order first
        $orderRepo = $this->container->make(OrderRepositoryInterface::class);
        $order->markAsPaid();
        $orderRepo->save($order);

        // Act
        $request = new Request(
            method: 'POST',
            uri: '/api/orders/order-paid/pay',
        );
        $response = $this->app->getRouter()->dispatch($request);

        // Assert
        $this->assertSame(422, $response->statusCode);
        $this->assertFalse($response->data['success']);
        $this->assertStringContainsString('already been paid', $response->data['error']);
    }

    #[Test]
    public function it_returns_422_when_order_is_cancelled(): void
    {
        // Arrange
        $order = $this->createOrder(id: 'order-cancelled', status: OrderStatus::Cancelled);

        // Act
        $request = new Request(
            method: 'POST',
            uri: '/api/orders/order-cancelled/pay',
        );
        $response = $this->app->getRouter()->dispatch($request);

        // Assert
        $this->assertSame(422, $response->statusCode);
        $this->assertFalse($response->data['success']);
        $this->assertStringContainsString('cannot be paid', $response->data['error']);
    }

    #[Test]
    public function it_persists_the_order_status_after_payment(): void
    {
        // Arrange
        $this->createOrder(id: 'order-persist');

        // Act
        $request = new Request(
            method: 'POST',
            uri: '/api/orders/order-persist/pay',
        );
        $this->app->getRouter()->dispatch($request);

        // Assert - fetch order directly from repository
        $orderRepo = $this->container->make(OrderRepositoryInterface::class);
        $updatedOrder = $orderRepo->find('order-persist');

        $this->assertNotNull($updatedOrder);
        $this->assertTrue($updatedOrder->getStatus()->isPaid());
        $this->assertNotNull($updatedOrder->getPaidAt());
    }

    #[Test]
    public function it_persists_payment_record_after_successful_payment(): void
    {
        // Arrange
        $this->createOrder(id: 'order-payment-record', amount: 7500, currency: 'EUR');

        // Act
        $request = new Request(
            method: 'POST',
            uri: '/api/orders/order-payment-record/pay',
        );
        $this->app->getRouter()->dispatch($request);

        // Assert - fetch payment directly from repository
        $paymentRepo = $this->container->make(PaymentRepositoryInterface::class);
        $payments = $paymentRepo->findByOrderId('order-payment-record');

        $this->assertCount(1, $payments);
        
        $payment = $payments[0];
        $this->assertSame('order-payment-record', $payment->orderId);
        $this->assertSame(7500, $payment->amount);
        $this->assertSame('EUR', $payment->currency);
        $this->assertSame(PaymentStatus::Completed, $payment->status);
        $this->assertSame('stripe', $payment->gatewayName);
    }

    #[Test]
    public function it_returns_correct_json_structure_on_success(): void
    {
        // Arrange
        $this->createOrder(id: 'order-json-structure');

        // Act
        $request = new Request(
            method: 'POST',
            uri: '/api/orders/order-json-structure/pay',
        );
        $response = $this->app->getRouter()->dispatch($request);

        // Assert - verify complete response structure
        $data = $response->data;

        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('order', $data);
        $this->assertArrayHasKey('payment', $data);

        // Order structure
        $this->assertArrayHasKey('id', $data['order']);
        $this->assertArrayHasKey('amount', $data['order']);
        $this->assertArrayHasKey('currency', $data['order']);
        $this->assertArrayHasKey('customer_email', $data['order']);
        $this->assertArrayHasKey('status', $data['order']);
        $this->assertArrayHasKey('paid_at', $data['order']);
        $this->assertArrayHasKey('created_at', $data['order']);

        // Payment structure
        $this->assertArrayHasKey('id', $data['payment']);
        $this->assertArrayHasKey('order_id', $data['payment']);
        $this->assertArrayHasKey('amount', $data['payment']);
        $this->assertArrayHasKey('currency', $data['payment']);
        $this->assertArrayHasKey('status', $data['payment']);
        $this->assertArrayHasKey('gateway_transaction_id', $data['payment']);
        $this->assertArrayHasKey('gateway_name', $data['payment']);
        $this->assertArrayHasKey('created_at', $data['payment']);
    }

    #[Test]
    public function get_order_endpoint_returns_order_details(): void
    {
        // Arrange
        $this->createOrder(
            id: 'order-get',
            amount: 3000,
            currency: 'GBP',
            customerEmail: 'get@example.com',
        );

        // Act
        $request = new Request(
            method: 'GET',
            uri: '/api/orders/order-get',
        );
        $response = $this->app->getRouter()->dispatch($request);

        // Assert
        $this->assertSame(200, $response->statusCode);
        $this->assertTrue($response->data['success']);
        $this->assertSame('order-get', $response->data['order']['id']);
        $this->assertSame(3000, $response->data['order']['amount']);
        $this->assertSame('GBP', $response->data['order']['currency']);
        $this->assertSame('pending', $response->data['order']['status']);
    }

    #[Test]
    public function list_orders_endpoint_returns_all_orders(): void
    {
        // Arrange
        $this->createOrder(id: 'order-list-1');
        $this->createOrder(id: 'order-list-2');
        $this->createOrder(id: 'order-list-3');

        // Act
        $request = new Request(
            method: 'GET',
            uri: '/api/orders',
        );
        $response = $this->app->getRouter()->dispatch($request);

        // Assert
        $this->assertSame(200, $response->statusCode);
        $this->assertTrue($response->data['success']);
        $this->assertCount(3, $response->data['orders']);
    }

    #[Test]
    public function it_returns_405_for_unsupported_methods(): void
    {
        // Act
        $request = new Request(
            method: 'DELETE',
            uri: '/api/orders/order-123/pay',
        );
        $response = $this->app->getRouter()->dispatch($request);

        // Assert
        $this->assertSame(405, $response->statusCode);
    }

    #[Test]
    public function it_returns_404_for_unknown_routes(): void
    {
        // Act
        $request = new Request(
            method: 'POST',
            uri: '/api/unknown/endpoint',
        );
        $response = $this->app->getRouter()->dispatch($request);

        // Assert
        $this->assertSame(404, $response->statusCode);
    }
}
