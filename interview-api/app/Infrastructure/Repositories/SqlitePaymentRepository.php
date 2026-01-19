<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Contracts\PaymentRepositoryInterface;
use App\Domain\Enums\PaymentStatus;
use App\Domain\Models\Payment;
use DateTimeImmutable;
use PDO;

final readonly class SqlitePaymentRepository implements PaymentRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
    ) {
    }

    public function find(string $id): ?Payment
    {
        $stmt = $this->pdo->prepare('SELECT * FROM payments WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findByOrderId(string $orderId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM payments WHERE order_id = :order_id ORDER BY created_at DESC'
        );
        $stmt->execute(['order_id' => $orderId]);
        $rows = $stmt->fetchAll();

        return array_map($this->hydrate(...), $rows);
    }

    public function save(Payment $payment): void
    {
        $stmt = $this->pdo->prepare(<<<SQL
            INSERT INTO payments (id, order_id, amount, currency, status, gateway_transaction_id, gateway_name, created_at, failure_reason)
            VALUES (:id, :order_id, :amount, :currency, :status, :gateway_transaction_id, :gateway_name, :created_at, :failure_reason)
        SQL);

        $stmt->execute([
            'id' => $payment->id,
            'order_id' => $payment->orderId,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'status' => $payment->status->value,
            'gateway_transaction_id' => $payment->gatewayTransactionId,
            'gateway_name' => $payment->gatewayName,
            'created_at' => $payment->createdAt->format('Y-m-d H:i:s'),
            'failure_reason' => $payment->failureReason,
        ]);
    }

    private function hydrate(array $row): Payment
    {
        return new Payment(
            id: $row['id'],
            orderId: $row['order_id'],
            amount: (int) $row['amount'],
            currency: $row['currency'],
            status: PaymentStatus::from($row['status']),
            gatewayTransactionId: $row['gateway_transaction_id'],
            gatewayName: $row['gateway_name'],
            createdAt: new DateTimeImmutable($row['created_at']),
            failureReason: $row['failure_reason'],
        );
    }
}
