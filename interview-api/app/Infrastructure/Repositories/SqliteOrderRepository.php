<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Contracts\OrderRepositoryInterface;
use App\Domain\Enums\OrderStatus;
use App\Domain\Models\Order;
use DateTimeImmutable;
use PDO;
use ReflectionClass;

final readonly class SqliteOrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
    ) {
    }

    public function find(string $id): ?Order
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM orders WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function save(Order $order): void
    {
        $stmt = $this->pdo->prepare(<<<SQL
            INSERT INTO orders (id, amount, currency, customer_email, status, paid_at, created_at)
            VALUES (:id, :amount, :currency, :customer_email, :status, :paid_at, :created_at)
            ON CONFLICT(id) DO UPDATE SET
                status = :status,
                paid_at = :paid_at
        SQL);

        $stmt->execute([
            'id' => $order->id,
            'amount' => $order->amount,
            'currency' => $order->currency,
            'customer_email' => $order->customerEmail,
            'status' => $order->getStatus()->value,
            'paid_at' => $order->getPaidAt()?->format('Y-m-d H:i:s'),
            'created_at' => $order->createdAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM orders ORDER BY created_at DESC');
        $rows = $stmt->fetchAll();

        return array_map($this->hydrate(...), $rows);
    }

    private function hydrate(array $row): Order
    {
        $order = new Order(
            id: $row['id'],
            amount: (int) $row['amount'],
            currency: $row['currency'],
            customerEmail: $row['customer_email'],
            status: OrderStatus::from($row['status']),
            createdAt: new DateTimeImmutable($row['created_at']),
        );

        // If the order was paid, we need to mark it as paid with the correct timestamp
        if ($row['status'] === OrderStatus::Paid->value && $row['paid_at'] !== null) {
            // Reconstruct the paid state by using reflection (necessary for immutable reconstruction)
            $reflection = new ReflectionClass($order);
            $statusProperty = $reflection->getProperty('status');
            $statusProperty->setValue($order, OrderStatus::Paid);
            $paidAtProperty = $reflection->getProperty('paidAt');
            $paidAtProperty->setValue($order, new DateTimeImmutable($row['paid_at']));
        }

        return $order;
    }
}
