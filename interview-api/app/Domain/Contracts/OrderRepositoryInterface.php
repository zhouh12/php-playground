<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\Models\Order;

/**
 * Interface for order persistence operations.
 * 
 * This interface belongs in the Domain layer as it defines
 * the contract for how the domain interacts with persistence,
 * without knowing implementation details.
 */
interface OrderRepositoryInterface
{
    /**
     * Find an order by its ID.
     *
     * @param string $id The order ID
     * @return Order|null The order if found, null otherwise
     */
    public function find(string $id): ?Order;

    /**
     * Save an order (create or update).
     *
     * @param Order $order The order to save
     * @return void
     */
    public function save(Order $order): void;

    /**
     * Get all orders.
     *
     * @return Order[]
     */
    public function all(): array;
}
