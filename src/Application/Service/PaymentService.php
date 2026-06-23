<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Application\Service;

use Crehler\PayU\Application\DTO\CreateOrder\{OrderRequest, OrderResponse};
use Crehler\PayU\Application\DTO\PaymentDTO;
use Crehler\PayU\Application\Port\Driven\{PaymentMethodResolverPort, PaymentServicePort};
use Crehler\PayU\Infrastructure\Port\PaymentGatewayPort;

final readonly class PaymentService implements PaymentServicePort
{
    public function __construct(
        private PaymentMethodResolverPort $paymentMethodResolver,
        private PaymentGatewayPort $paymentGateway,
    ) {
    }

    public function pay(PaymentDTO $paymentDTO): OrderResponse
    {
        $payMethod = $this->paymentMethodResolver->resolve(
            customer: $paymentDTO->order->customer,
            paymentMethod: $paymentDTO->orderTransaction->paymentMethod,
            authorizationCode: $paymentDTO->authorizeCode,
            fingerPrintDevice: $paymentDTO->fingerPrintDevice,
            paymentSubMethodId: $paymentDTO->paymentSubMethodId,
        );

        $request = new OrderRequest(
            payMethod: $payMethod,
            orderTransaction: $paymentDTO->orderTransaction,
            notifyUrl: $paymentDTO->notifyUrl,
            customerIp: $paymentDTO->customerIp,
            continueUrl: $paymentDTO->continueUrl,
            salesChannelId: $paymentDTO->salesChannelId
        );

        return $this->paymentGateway->createOrder(request: $request);
    }
}
