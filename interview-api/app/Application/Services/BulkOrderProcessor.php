<?php
declare(strict_types=1);

use App\Application\DTO\OrderResponse;
use App\Domain\Contracts\OrderRepositoryInterface;
use App\Domain\Enums\OrderStatus;

interface IBulkOrderProcessor
{
    /**
     * @return OrderResponse[]
     */
    public function processBulkOrders(array $orderIds, string $customerEmail): array;
}

final readonly class BulkOrderProcessor implements IBulkOrderProcessor
{
    public function __construct(public PDO $db){}

    private function calculateDiscount($order): float
    {
        $amount = (float)$order['amount'];
        $discount = 0;
        
        if ($amount >= 1000) {
            $discount = $amount * 0.1; // 10% discount for orders >= 1000
        } elseif ($amount >= 500) {
            $discount = $amount * 0.05; // 5% discount for orders >= 500
        }
        return $discount;
    } 

    private function updateOrderStatus(string $orderId, float $finalAmount)
    {
        $updateStmt = $this->db->prepare("UPDATE orders SET status = 'paid', amount = ? WHERE id = ?");
        $updateStmt->execute([$finalAmount, $orderId]);
    }

    private function logPayment(string $orderId, float $finalAmount)
    {
        $logStmt = $this->db->prepare("INSERT INTO payments (order_id, amount, status, created_at) VALUES (?, ?, 'completed', datetime('now'))");
        $logStmt->execute([$orderId, $finalAmount]);
    }

    public function processBulkOrders(array $orderIds, string $customerEmail): array
    {
        $results = [];
        $totalDiscount = 0;

        if (empty($customerEmail) || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $results[] = new OrderResponse(
                orderId: '',
                status: OrderStatus::NotFound,
                originalAmount: 0.0,
                discount: 0,
                finalAmount: 0.0,
                error: 'ERROR: Invalid customer email'
            );
        }

        foreach($orderIds as $orderId){
            try {
                $stmt = $this->db->prepare("SELECT id, amount, currency, status FROM orders WHERE id = ?");
                $stmt->execute([$orderId]);
                $order = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$order) {
                    $results[] = new OrderResponse(
                        orderId: $orderId,
                        status: OrderStatus::NotFound,
                        originalAmount: 0.0,
                        discount: 0,
                        finalAmount: 0.0,
                        error: 'Order not found'
                    );
                    continue;
                }

                if ($order['status'] === 'paid') {
                    echo "WARNING: Order $orderId is already paid\n";
                    $results[] = ['orderId' => $orderId, 'status' => 'already_paid', 'amount' => $order['amount']];
                    continue;
                }
                
                $amount = (float)$order['amount'];
                $discount = $this->calculateDiscount($order);
                $finalAmount = $amount - $discount;
                $totalDiscount += $discount;

                $this->updateOrderStatus($orderId, $finalAmount);
                $this->logPayment($orderId, $finalAmount);

                $results[] = new OrderResponse(
                    orderId: $orderId,
                    status: OrderStatus::Paid,
                    originalAmount: 0.0,
                    discount: 0,
                    finalAmount: 0.0,
                    error: 'Order not found'
                );
            } catch (\Throwable $th) {
                $results[] = new OrderResponse(
                    orderId: $orderId,
                    status: OrderStatus::Error,
                    originalAmount: 0.0,
                    discount: 0,
                    finalAmount: 0.0,
                    error: 'Order not found'
                );
            }
        }

        return $results;
    }
}