<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Infrastructure\Adapter;

use Crehler\PaymentBundle\Shared\{EnhancedLogger, Serializer};
use Crehler\PayU\Application\DTO\CreateOrder\{OrderRequest, OrderResponse};
use Crehler\PayU\Application\DTO\{OrderNotificationDTO, PaymentMethodResponse};
use Crehler\PayU\Application\Port\Driven\{PaymentMethodServicePort, PaymentStatusResolverPort};
use Crehler\PayU\Infrastructure\Client\{PayUClient, PayUClientFactory};
use Crehler\PayU\Infrastructure\Exception\ConfigurationException;
use Crehler\PayU\Infrastructure\Mapper\PaymentRequestMapper;
use Crehler\PayU\Infrastructure\Port\PaymentGatewayPort;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Symfony\Component\HttpFoundation\{Request, Response};
use Throwable;

final readonly class PaymentGatewayAdapter implements PaymentGatewayPort
{
    public function __construct(
        private EnhancedLogger $logger,
        private PaymentStatusResolverPort $paymentStatusResolverPort,
        private PaymentMethodServicePort $paymentMethodServicePort,
        private PayUClient $payUClient,
        private PayUClientFactory $payUClientFactory,
        private PaymentRequestMapper $paymentRequestMapper,
    ) {
    }

    public function createOrder(OrderRequest $request): OrderResponse
    {
        try {
            $this->initialize($request->salesChannelId);

            $request = $request->withMerchantPosId(
                orderRequest: $request,
                merchantPosId: $this->payUClient->getMerchantPosId()
            );

            $mappedData = $this->paymentRequestMapper->mapOrderTransactionToPaymentRequest(req: $request);

            $data = Serializer::getSerializer()->normalize($mappedData, Serializer::JSON_FORMAT);

            // Log only correlation-safe fields. Never log notifyUrl/continueUrl
            // (they carry the _sw_payment_token finalize secret) or payMethods
            // (carries the reusable PayU card token on tokenized payments).
            $this->logger->info('PayU creating order', [
                'extOrderId' => $data['extOrderId'] ?? null,
                'orderTransactionId' => $request->orderTransaction->id,
                'shopwareOrderId' => $request->orderTransaction->order->id,
                'orderNumber' => $request->orderTransaction->order->orderNumber,
                'totalAmount' => $data['totalAmount'] ?? null,
                'currencyCode' => $data['currencyCode'] ?? null,
                'merchantPosId' => $data['merchantPosId'] ?? null,
                'hasPayMethod' => isset($data['payMethods']),
                'salesChannelId' => $request->salesChannelId,
            ]);

            $response = $this->payUClient->createOrder($data);

            $orderResponse = $this->paymentStatusResolverPort->resolve(
                response: $response,
                orderId: $request->orderTransaction->order->id
            );

            // Never log redirectUri — it can carry the gateway continue URL / tokens.
            $this->logger->info('PayU order created', [
                'extOrderId' => $data['extOrderId'] ?? null,
                'payuOrderId' => $orderResponse->orderId,
                'status' => $orderResponse->status,
                'hasRedirectUri' => $orderResponse->redirectUri !== null && $orderResponse->redirectUri !== '',
                'payuResponseStatus' => $response->getStatus(),
            ]);

            return $orderResponse;
        } catch (Throwable $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);

            return new OrderResponse(
                status: false,
                code: Response::HTTP_BAD_REQUEST,
                error: $e->getMessage()
            );
        }
    }

    public function getAvailablePaymentMethods(
        string $paymentMethodId,
        ?CustomerEntity $customerEntity = null,
        string $lang = 'pl',
        int $checkoutValue = 0,
        string $salesChannelId = '',
    ): PaymentMethodResponse {
        try {
            $this->initialize(salesChannelId: $salesChannelId);

            $isGuest = $customerEntity?->getGuest();

            if (!$isGuest && $customerEntity) {
                $this->setTrustedMerchant($customerEntity->getId(), $customerEntity->getEmail());
            }

            $payuResponse = $this->payUClient->retrievePayMethods(lang: $lang);

            return $this->paymentMethodServicePort->getPaymentMethods(
                paymentMethods: $payuResponse,
                checkoutValue: $checkoutValue,
                shopwarePaymentId: $paymentMethodId,
            );
        } catch (Throwable $e) {
            if ($e instanceof ConfigurationException) {
                $this->logger->critical('PayU configuration error', ['exception' => $e]);
            } else {
                $this->logger->error('PayU payment methods error', ['exception' => $e]);
            }

            return new PaymentMethodResponse(
                status: 'FAILED',
                payByLinks: [],
                savedCards: []
            );
        }
    }

    public function customerOwnsCardToken(
        string $token,
        string $customerId,
        string $customerEmail,
        ?string $salesChannelId = null,
    ): bool {
        if ($token === '') {
            return false;
        }

        try {
            $this->initialize(salesChannelId: $salesChannelId ?? '');
            $this->setTrustedMerchant($customerId, $customerEmail);

            $payuResponse = $this->payUClient->retrievePayMethods();
            $cardTokens = $payuResponse->cardTokens ?? [];

            foreach ($cardTokens as $cardToken) {
                if (isset($cardToken->value) && $cardToken->value === $token) {
                    return true;
                }
            }

            return false;
        } catch (Throwable $e) {
            // Fail closed: if we cannot prove ownership, do not honor the token.
            $this->logger->error('PayU card token ownership check failed', [
                'customerId' => $customerId,
                'salesChannelId' => $salesChannelId,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function verifyNotification(Request $request, ?string $salesChannelId = null): OrderNotificationDTO
    {
        try {
            // Do not log the raw notification body (buyer PII) or the
            // openpayu-signature header value — log only that a signature is present.
            $this->logger->info('PayU notification received', [
                'hasSignature' => $request->headers->has('openpayu-signature'),
                'contentType' => $request->headers->get('content-type'),
                'salesChannelId' => $salesChannelId,
            ]);

            $this->initialize($salesChannelId);

            $notification = $this->payUClient->consumeNotification($request);
            $response = $notification->getResponse();

            $order = $this->payUClient->retrieveOrder($response->order->orderId);

            // Refund notifications carry both the order and a refund object
            // (refundId + status PENDING/FINALIZED/CANCELED + amount in grosze).
            $refund = $response->refund ?? null;

            $this->logger->info('PayU notification parsed', [
                'payuOrderId' => $response->order->orderId,
                'extOrderId' => $response->order->extOrderId,
                'notificationStatus' => $response->order->status,
                'retrievedOrderStatus' => $order->getStatus(),
                'refundId' => isset($refund->refundId) ? (string) $refund->refundId : null,
                'refundStatus' => isset($refund->status) ? (string) $refund->status : null,
            ]);

            return new OrderNotificationDTO(
                status: true,
                orderId: $response->order->orderId,
                shopOrderId: $response->order->extOrderId,
                orderStatus: $order->getStatus(),
                paymentStatus: $response->order->status,
                notification: $response,
                refundId: isset($refund->refundId) ? (string) $refund->refundId : null,
                refundStatus: isset($refund->status) ? (string) $refund->status : null,
                refundAmount: isset($refund->amount) ? (int) $refund->amount : null,
            );
        } catch (Throwable $e) {
            $this->logger->error('PayU notification error', ['exception' => $e]);

            return new OrderNotificationDTO(
                status: false,
                error: $e->getMessage(),
                infrastructureError: true,
            );
        }
    }

    public function isSandboxEnabled(?string $salesChannelId = null): bool
    {
        return $this->payUClientFactory->isSandboxEnabled($salesChannelId);
    }

    /**
     * @throws ConfigurationException
     */
    private function initialize(?string $salesChannelId = null): void
    {
        $this->payUClientFactory->create($salesChannelId);
    }

    private function setTrustedMerchant(string $customerId, string $customerEmail): void
    {
        $this->payUClient->setTrustedMerchant($customerId, $customerEmail);
    }
}
