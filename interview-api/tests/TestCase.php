<?php

declare(strict_types=1);

namespace Tests;

use App\Domain\Contracts\OrderRepositoryInterface;
use App\Domain\Enums\OrderStatus;
use App\Domain\Models\Order;
use App\Infrastructure\Container\Container;
use App\Infrastructure\Database\Connection;
use Bootstrap\Application;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected Application $app;
    protected Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset database connection for each test
        Connection::reset();

        // Create application with in-memory SQLite
        $this->app = new Application('sqlite::memory:');
        $this->app->migrate();
        
        $this->container = $this->app->getContainer();
    }

    protected function tearDown(): void
    {
        Connection::reset();
        parent::tearDown();
    }

    /**
     * Create a test order in the database.
     */
    protected function createOrder(
        string $id = 'order-123',
        int $amount = 10000,
        string $currency = 'USD',
        string $customerEmail = 'test@example.com',
        OrderStatus $status = OrderStatus::Pending,
    ): Order {
        $order = new Order(
            id: $id,
            amount: $amount,
            currency: $currency,
            customerEmail: $customerEmail,
            status: $status,
        );

        $repository = $this->container->make(OrderRepositoryInterface::class);
        $repository->save($order);

        return $order;
    }
}
