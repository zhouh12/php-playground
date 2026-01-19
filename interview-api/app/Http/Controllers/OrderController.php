<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Request;
use App\Http\JsonResponse;
use App\Domain\Exceptions\PaymentException;
use App\Application\Services\PaymentService;
use App\Domain\Exceptions\OrderNotFoundException;
use App\Domain\Contracts\OrderRepositoryInterface;
use OpenApi\Attributes as OA;

/**
 * Controller for order-related endpoints.
 * 
 * @OA\Tag(name="Orders", description="Order management endpoints")
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
    #[OA\Post(
        path: '/api/orders/{orderId}/pay',
        summary: 'Process payment for an order',
        description: 'Processes a payment for the specified order and updates the order status to paid',
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(
                name: 'orderId',
                in: 'path',
                required: true,
                description: 'The ID of the order to process payment for',
                schema: new OA\Schema(type: 'string', example: 'order-123')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment processed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Payment processed successfully'),
                        new OA\Property(
                            property: 'order',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string'),
                                new OA\Property(property: 'amount', type: 'integer'),
                                new OA\Property(property: 'currency', type: 'string'),
                                new OA\Property(property: 'status', type: 'string', example: 'paid'),
                            ]
                        ),
                        new OA\Property(
                            property: 'payment',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string'),
                                new OA\Property(property: 'status', type: 'string', example: 'completed'),
                                new OA\Property(property: 'gateway_transaction_id', type: 'string'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Order not found'),
            new OA\Response(response: 422, description: 'Order cannot be paid (already paid, cancelled, etc.)'),
        ]
    )]
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
    #[OA\Get(
        path: '/api/orders/{orderId}',
        summary: 'Get order details',
        description: 'Retrieves details for a specific order',
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(
                name: 'orderId',
                in: 'path',
                required: true,
                description: 'The ID of the order',
                schema: new OA\Schema(type: 'string', example: 'order-123')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Order details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'order',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string'),
                                new OA\Property(property: 'amount', type: 'integer'),
                                new OA\Property(property: 'currency', type: 'string'),
                                new OA\Property(property: 'status', type: 'string'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Order not found'),
        ]
    )]
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
    #[OA\Get(
        path: '/api/orders',
        summary: 'List all orders',
        description: 'Retrieves a list of all orders',
        tags: ['Orders'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of orders',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'orders',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'string'),
                                    new OA\Property(property: 'amount', type: 'integer'),
                                    new OA\Property(property: 'currency', type: 'string'),
                                    new OA\Property(property: 'status', type: 'string'),
                                ]
                            )
                        ),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request, array $params): JsonResponse
    {
        $orders = $this->orderRepository->all();

        return JsonResponse::success([
            'success' => true,
            'orders' => array_map(fn($order) => $order->toArray(), $orders),
        ]);
    }
}
