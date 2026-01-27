<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Request;
use App\Http\JsonResponse;

/**
 * Legacy Bulk Order Controller
 * This is a legacy endpoint that needs refactoring
 */
final class LegacyBulkOrderController
{
    public function process(Request $request, array $params): JsonResponse
    {
        require_once __DIR__ . '/../Legacy/BulkOrderProcessor.php';
        
        $processor = new \BulkOrderProcessor();
        
        $body = $request->body;
        $orderIds = $body['orderIds'] ?? [];
        $customerEmail = $body['customerEmail'] ?? '';
        
        $results = $processor->processBulkOrders($orderIds, $customerEmail);
        
        return JsonResponse::success([
            'message' => 'Bulk order processing completed',
            'results' => $results
        ]);
    }
}
