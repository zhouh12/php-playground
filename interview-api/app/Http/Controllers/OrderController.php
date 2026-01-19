<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Request;
use App\Http\JsonResponse;
use App\Domain\Exceptions\PaymentException;
use App\Application\Services\PaymentService;
use App\Domain\Exceptions\OrderNotFoundException;
use App\Domain\Contracts\OrderRepositoryInterface;

/**
 * Controller for order-related endpoints.
 */
final readonly class OrderController
{
    public function __construct(
        private PaymentService $paymentService,
        private OrderRepositoryInterface $orderRepository,
    ) {
    }

    /**
     * POST /api/orders/{orderId}/pay
     * 
     * Process payment for an order.
     */
    public function pay(Request $request, array $params): JsonResponse
    {
        $orderId = $params['orderId'] ?? null;

        if ($orderId === null || $orderId === '') {
            return JsonResponse::error('Order ID is required', 400);
        }

        try {
            $response = $this->paymentService->processPayment($orderId);
            
            return JsonResponse::success($response->toArray());
        } catch (OrderNotFoundException $e) {
            return JsonResponse::notFound($e->getMessage());
        } catch (PaymentException $e) {
            return JsonResponse::error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            // Log the error in production
            return JsonResponse::serverError('An unexpected error occurred');
        }
    }

    /**
     * GET /api/orders/{orderId}
     * 
     * Get order details.
     */
    public function show(Request $request, array $params): JsonResponse
    {
        $orderId = $params['orderId'] ?? null;

        if ($orderId === null || $orderId === '') {
            return JsonResponse::error('Order ID is required', 400);
        }

        $order = $this->orderRepository->find($orderId);

        if ($order === null) {
            return JsonResponse::notFound("Order not found: {$orderId}");
        }

        return JsonResponse::success([
            'success' => true,
            'order' => $order->toArray(),
        ]);
    }

    /**
     * GET /api/orders
     * 
     * List all orders.
     */
    public function index(Request $request, array $params): JsonResponse
    {
        $orders = $this->orderRepository->all();

        return JsonResponse::success([
            'success' => true,
            'orders' => array_map(fn($order) => $order->toArray(), $orders),
        ]);
    }
}
