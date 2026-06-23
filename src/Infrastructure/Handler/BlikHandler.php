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
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request};
use Symfony\Component\Routing\RouterInterface;

final class BlikHandler extends AbstractPaymentMethodHandler
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

    public function supports(PaymentHandlerType $type, string $paymentMethodId, Context $context): bool
    {
        return false;
    }

    /**
     * BLIK uses the shared layer-zero flow: authorize in-place on a BLIK code,
     * otherwise redirect to the PayU payment page.
     */
    public function pay(
        Request $request,
        PaymentTransactionStruct $transaction,
        Context $context,
        ?Struct $validateStruct,
    ): ?RedirectResponse {
        return $this->payViaBlikAuthorize($request, $transaction, $context);
    }

    protected function getPaymentProviderName(): string
    {
        return 'PayU BLIK';
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
        $authorizationCode = $request->get('blikCode');

        ['notifyUrl' => $notifyUrl] = $this->buildPaymentUrls($orderTransaction);

        $paymentDTO = new PaymentDTO(
            order: $orderTransaction->order,
            orderTransaction: $orderTransaction,
            continueUrl: $transaction->getReturnUrl(),
            customerIp: $request->getClientIp(),
            notifyUrl: $notifyUrl,
            authorizeCode: $authorizationCode,
        );

        $response = $this->paymentServicePort->pay(paymentDTO: $paymentDTO);

        if (!$response->isSuccess()) {
            throw new Exception($response->error ?? 'BLIK payment initialization failed');
        }

        $this->persistGatewayPaymentId($transaction->getOrderTransactionId(), $response->orderId, $context);

        return PaymentResult::success(
            redirectUrl: $response->redirectUri ?? '',
            gatewayOrderId: $response->orderId
        );
    }
}
