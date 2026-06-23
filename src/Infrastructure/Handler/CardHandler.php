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
use Crehler\PayU\Infrastructure\Port\PaymentGatewayPort;
use Exception;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

use function is_string;

final class CardHandler extends AbstractPaymentMethodHandler
{
    public function __construct(
        EnhancedLogger $logger,
        RouterInterface $router,
        OrderTransactionServicePort $orderTransactionServicePort,
        FinalizeTokenService $finalizeTokenService,
        PaymentSubMethodSessionResolverPort $paymentSubMethodSessionResolver,
        OrderTransactionRepositoryInterface $orderTransactionRepository,
        private readonly PaymentServicePort $paymentServicePort,
        private readonly PaymentGatewayPort $paymentGateway,
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
        return 'PayU Card';
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
        $cardToken = $request->get('payuCardToken');
        $visitorId = $request->get('visitorId');

        // A saved-card token is a reusable PayU bearer value. The hidden form field
        // is not an authorization boundary, so re-prove ownership server-side before
        // honoring it: it must belong to the authenticated customer on this sales
        // channel. Reject otherwise instead of forwarding an attacker-supplied token.
        if (is_string($cardToken) && $cardToken !== '') {
            $customer = $orderTransaction->order->customer;

            $owns = !$customer->isGuest && $this->paymentGateway->customerOwnsCardToken(
                token: $cardToken,
                customerId: $customer->id,
                customerEmail: $customer->email,
                salesChannelId: $orderTransaction->order->salesChannelId,
            );

            if (!$owns) {
                $this->logger->warning('PayU: rejected card token not owned by customer', [
                    'orderTransactionId' => $orderTransaction->id,
                    'customerId' => $customer->id,
                ]);

                throw new Exception('The selected saved card is not available for this customer.');
            }
        }

        ['notifyUrl' => $notifyUrl] = $this->buildPaymentUrls($orderTransaction);

        $paymentDTO = new PaymentDTO(
            order: $orderTransaction->order,
            orderTransaction: $orderTransaction,
            continueUrl: $transaction->getReturnUrl(),
            customerIp: $request->getClientIp(),
            notifyUrl: $notifyUrl,
            authorizeCode: $cardToken,
            fingerPrintDevice: $visitorId,
        );

        $response = $this->paymentServicePort->pay(paymentDTO: $paymentDTO);

        if (!$response->isSuccess()) {
            throw new Exception($response->error ?? 'Card payment initialization failed');
        }

        $this->persistGatewayPaymentId($transaction->getOrderTransactionId(), $response->orderId, $context);

        return PaymentResult::success(
            redirectUrl: $response->redirectUri,
            gatewayOrderId: $response->orderId
        );
    }
}
