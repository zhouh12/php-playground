<?php

class BulkOrderProcessor
{
    private $db;
    
    public function __construct()
    {
        $this->db = new PDO('sqlite:' . __DIR__ . '/../../database/database.sqlite');
    }
    
    /**
     * Process multiple orders with discounts and validation
     * Legacy implementation - needs refactoring
     */
    public function processBulkOrders($orderIds, $customerEmail)
    {
        $results = [];
        $totalDiscount = 0;
        $processedCount = 0;
        $failedCount = 0;
        
        echo "Starting bulk order processing for customer: $customerEmail\n";
        
        // Validate customer email
        if (empty($customerEmail) || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            echo "ERROR: Invalid customer email\n";
            return;
        }
        
        // Process each order
        foreach ($orderIds as $orderId) {
            try {
                // Fetch order from database
                $stmt = $this->db->prepare("SELECT id, amount, currency, status FROM orders WHERE id = ?");
                $stmt->execute([$orderId]);
                $order = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$order) {
                    echo "ERROR: Order $orderId not found\n";
                    $failedCount++;
                    $results[] = ['orderId' => $orderId, 'status' => 'not_found', 'error' => 'Order not found'];
                    continue;
                }
                
                // Check if order is already paid
                if ($order['status'] === 'paid') {
                    echo "WARNING: Order $orderId is already paid\n";
                    $results[] = ['orderId' => $orderId, 'status' => 'already_paid', 'amount' => $order['amount']];
                    continue;
                }
                
                // Calculate discount based on order amount
                $amount = (float)$order['amount'];
                $discount = 0;
                
                if ($amount >= 1000) {
                    $discount = $amount * 0.1; // 10% discount for orders >= 1000
                    echo "Applied 10% discount to order $orderId\n";
                } elseif ($amount >= 500) {
                    $discount = $amount * 0.05; // 5% discount for orders >= 500
                    echo "Applied 5% discount to order $orderId\n";
                }
                
                $finalAmount = $amount - $discount;
                $totalDiscount += $discount;
                
                // Update order status
                $updateStmt = $this->db->prepare("UPDATE orders SET status = 'paid', amount = ? WHERE id = ?");
                $updateStmt->execute([$finalAmount, $orderId]);
                
                // Log payment
                $logStmt = $this->db->prepare("INSERT INTO payments (order_id, amount, status, created_at) VALUES (?, ?, 'completed', datetime('now'))");
                $logStmt->execute([$orderId, $finalAmount]);
                
                echo "SUCCESS: Order $orderId processed. Original: $amount, Discount: $discount, Final: $finalAmount\n";
                
                $processedCount++;
                $results[] = [
                    'orderId' => $orderId,
                    'status' => 'processed',
                    'originalAmount' => $amount,
                    'discount' => $discount,
                    'finalAmount' => $finalAmount
                ];
                
            } catch (Exception $e) {
                echo "ERROR processing order $orderId: " . $e->getMessage() . "\n";
                $failedCount++;
                $results[] = ['orderId' => $orderId, 'status' => 'error', 'error' => $e->getMessage()];
            }
        }
        
        // Summary
        echo "\n=== Processing Summary ===\n";
        echo "Total orders: " . count($orderIds) . "\n";
        echo "Processed: $processedCount\n";
        echo "Failed: $failedCount\n";
        echo "Total discount applied: $totalDiscount\n";
        
        return $results;
    }
    
    /**
     * Get order statistics for a customer
     */
    public function getCustomerOrderStats($customerEmail)
    {
        if (empty($customerEmail)) {
            echo "ERROR: Customer email required\n";
            return null;
        }
        
        $stmt = $this->db->prepare("SELECT COUNT(*) as total, SUM(amount) as total_amount FROM orders WHERE customer_email = ?");
        $stmt->execute([$customerEmail]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Customer: $customerEmail\n";
        echo "Total orders: " . ($stats['total'] ?? 0) . "\n";
        echo "Total amount: " . ($stats['total_amount'] ?? 0) . "\n";
        
        return $stats;
    }
}
