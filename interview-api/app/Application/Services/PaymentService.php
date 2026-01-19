<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\IdGeneratorInterface;
use App\Application\Contracts\PaymentGatewayInterface;
use App\Application\DTO\PaymentResponse;
use App\Domain\Contracts\OrderRepositoryInterface;
use App\Domain\Contracts\PaymentRepositoryInterface;
use App\Domain\Exceptions\OrderNotFoundException;
use App\Domain\Exceptions\PaymentException;
use App\Domain\Models\Payment;

/**
 * Application service responsible for processing payments.
 * 
 * This service orchestrates the payment flow:
 * 1. Validates the order exists and can be paid
 * 2. Processes payment through the gateway
 * 3. Records the payment and updates the order
 */
final readonly class PaymentService
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private PaymentRepositoryInterface $paymentRepository,
        private PaymentGatewayInterface $paymentGateway,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    /**
     * Process payment for an order.
     *
     * @param string $orderId The ID of the order to pay
     * @return PaymentResponse The result of the payment attempt
     * @throws OrderNotFoundException If the order doesn't exist
     * @throws PaymentException If the order cannot be paid
     */
    public function processPayment(string $orderId): PaymentResponse
    {
        // 1. Find the order
        $order = $this->orderRepository->find($orderId);
        
        if ($order === null) {
            throw OrderNotFoundException::withId($orderId);
        }

        // 2. Validate order can be paid
        if ($order->getStatus()->isPaid()) {
            throw PaymentException::orderAlreadyPaid($orderId);
        }

        if (!$order->getStatus()->canBePaid()) {
            throw PaymentException::orderNotPayable(
                $orderId,
                $order->getStatus()->value
            );
        }

        // 3. Validate currency support
        if (!$this->paymentGateway->supportsCurrency($order->currency)) {
            throw PaymentException::currencyNotSupported(
                $order->currency,
                $this->paymentGateway->getName()
            );
        }

        // 4. Process payment through gateway
        $result = $this->paymentGateway->charge($order);

        // 5. Create payment record
        $payment = $result->isSuccessful()
            ? Payment::successful(
                id: $this->idGenerator->generate(),
                orderId: $order->id,
                amount: $order->amount,
                currency: $order->currency,
                gatewayTransactionId: $result->getTransactionId(),
                gatewayName: $this->paymentGateway->getName(),
            )
            : Payment::failed(
                id: $this->idGenerator->generate(),
                orderId: $order->id,
                amount: $order->amount,
                currency: $order->currency,
                gatewayTransactionId: $result->getTransactionId(),
                gatewayName: $this->paymentGateway->getName(),
                failureReason: $result->getFailureReason() ?? 'Unknown error',
            );

        // 6. Save payment record
        $this->paymentRepository->save($payment);

        // 7. Update order if payment successful
        if ($payment->isSuccessful()) {
            $order->markAsPaid();
            $this->orderRepository->save($order);

            return PaymentResponse::success($order, $payment);
        }

        // 8. Return failure response
        throw PaymentException::gatewayFailed(
            $result->getFailureReason() ?? 'Payment declined'
        );
    }
}
