<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Infrastructure\Handler;

use Crehler\PaymentBundle\Application\Port\Driven\{OrderTransactionRepositoryInterface, PaymentSubMethodSessionResolverPort};
use Crehler\PaymentBundle\Application\Port\Driving\OrderTransactionServicePort;
use Crehler\PaymentBundle\Domain\Entity\OrderTransaction\OrderTransaction;
use Crehler\PaymentBundle\Infrastructure\Handler\{AbstractPaymentMethodHandler, PaymentResult};
use Crehler\PaymentBundle\Shared\{EnhancedLogger, FinalizeTokenService};
use Crehler\PayU\Application\DTO\PaymentDTO;
use Crehler\PayU\Application\Port\Driven\PaymentServicePort;
use Exception;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

final class DeferredHandler extends AbstractPaymentMethodHandler
{
    public function __construct(
        EnhancedLogger $logger,
        RouterInterface $router,
        OrderTransactionServicePort $orderTransactionServicePort,
        FinalizeTokenService $finalizeTokenService,
        PaymentSubMethodSessionResolverPort $paymentSubMethodSessionResolver,
        OrderTransactionRepositoryInterface $orderTransactionRepository,
        private readonly PaymentServicePort $paymentServicePort,
    ) {
        parent::__construct(
            $logger,
            $router,
            $orderTransactionServicePort,
            $finalizeTokenService,
            $paymentSubMethodSessionResolver,
            $orderTransactionRepository,
        );
    }

    protected function getPaymentProviderName(): string
    {
        return 'PayU Deferred';
    }

    protected function getProviderLogo(): string
    {
        return 'payu';
    }

    protected function processPayment(
        Request $request,
        PaymentTransactionStruct $transaction,
        OrderTransaction $orderTransaction,
        ?string $paymentSubMethodId,
        Context $context,
    ): PaymentResult {
        ['notifyUrl' => $notifyUrl] = $this->buildPaymentUrls($orderTransaction);

        $paymentDTO = new PaymentDTO(
            order: $orderTransaction->order,
            orderTransaction: $orderTransaction,
            continueUrl: $transaction->getReturnUrl(),
            customerIp: $request->getClientIp(),
            notifyUrl: $notifyUrl,
            paymentSubMethodId: $paymentSubMethodId,
        );

        $response = $this->paymentServicePort->pay(paymentDTO: $paymentDTO);

        if (!$response->isSuccess()) {
            throw new Exception($response->error ?? 'Deferred payment initialization failed');
        }

        $this->persistGatewayPaymentId($transaction->getOrderTransactionId(), $response->orderId, $context);

        return PaymentResult::success(
            redirectUrl: $response->redirectUri,
            gatewayOrderId: $response->orderId
        );
    }
}
