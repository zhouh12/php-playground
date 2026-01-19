<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

final readonly class DatabaseMigrator
{
    public function __construct(
        private PDO $pdo,
    ) {
    }

    public function migrate(): void
    {
        $this->createOrdersTable();
        $this->createPaymentsTable();
    }

    private function createOrdersTable(): void
    {
        $this->pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS orders (
                id TEXT PRIMARY KEY,
                amount INTEGER NOT NULL,
                currency TEXT NOT NULL,
                customer_email TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'pending',
                paid_at TEXT NULL,
                created_at TEXT NOT NULL
            )
        SQL);
    }

    private function createPaymentsTable(): void
    {
        $this->pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS payments (
                id TEXT PRIMARY KEY,
                order_id TEXT NOT NULL,
                amount INTEGER NOT NULL,
                currency TEXT NOT NULL,
                status TEXT NOT NULL,
                gateway_transaction_id TEXT NOT NULL,
                gateway_name TEXT NOT NULL,
                created_at TEXT NOT NULL,
                failure_reason TEXT NULL,
                FOREIGN KEY (order_id) REFERENCES orders(id)
            )
        SQL);
    }
}
