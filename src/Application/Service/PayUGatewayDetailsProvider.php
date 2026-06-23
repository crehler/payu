<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Application\Service;

use Crehler\PaymentBundle\Application\DTO\GatewayDetails\{GatewayPaymentDetails, GatewayStatusLevel};
use Crehler\PaymentBundle\Application\Port\Driven\GatewayPaymentDetailsProviderInterface;
use Crehler\PaymentBundle\Domain\Constant\PaymentCustomFields;
use Crehler\PaymentBundle\Shared\EnhancedLogger;
use Crehler\PayU\Infrastructure\Client\PayUClientFactory;
use Crehler\PayU\Infrastructure\Handler\{BankHandler, BlikHandler, CardHandler, DeferredHandler, EWalletHandler};
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Throwable;

use function in_array;
use function strtoupper;

/**
 * Exposes PayU order details (OpenPayU OrderRetrieve) for the admin order
 * "Szczegóły" tab.
 */
final readonly class PayUGatewayDetailsProvider implements GatewayPaymentDetailsProviderInterface
{
    private const HANDLERS = [
        BlikHandler::class,
        BankHandler::class,
        CardHandler::class,
        EWalletHandler::class,
        DeferredHandler::class,
    ];

    public function __construct(
        private PayUClientFactory $payUClientFactory,
        private EnhancedLogger $logger,
    ) {
    }

    public function supports(OrderTransactionEntity $orderTransaction): bool
    {
        return in_array($orderTransaction->getPaymentMethod()?->getHandlerIdentifier(), self::HANDLERS, true);
    }

    public function getDetails(OrderTransactionEntity $orderTransaction): ?GatewayPaymentDetails
    {
        $customFields = $orderTransaction->getCustomFields();
        $gatewayId = $customFields[PaymentCustomFields::GATEWAY_PAYMENT_ID] ?? null;
        if (!$gatewayId) {
            return null;
        }

        try {
            $salesChannelId = $orderTransaction->getOrder()?->getSalesChannelId();
            $client = $this->payUClientFactory->create($salesChannelId);
            $order = $client->retrieveOrder((string) $gatewayId)->getResponse()->orders[0] ?? null;

            if ($order === null) {
                return null;
            }

            $status = (string) ($order->status ?? 'unknown');
            // PayU amounts are in minor units (grosze).
            $amountMinor = isset($order->totalAmount) ? (int) $order->totalAmount : null;

            return new GatewayPaymentDetails(
                provider: 'PayU',
                gatewayId: (string) ($order->orderId ?? $gatewayId),
                rawStatus: $status,
                statusLevel: $this->mapLevel($status),
                amount: $amountMinor !== null ? $amountMinor / 100 : null,
                currency: isset($order->currencyCode) ? (string) $order->currencyCode : null,
                method: isset($order->payMethod->type) ? (string) $order->payMethod->type : null,
                createdAt: isset($order->orderCreateDate) ? (string) $order->orderCreateDate : null,
                title: isset($order->description) ? (string) $order->description : null,
                sandbox: $this->payUClientFactory->isSandboxEnabled($salesChannelId),
            );
        } catch (Throwable $e) {
            $this->logger->error('Failed to load PayU gateway details', [
                'orderTransactionId' => $orderTransaction->getId(),
                'gatewayId' => $gatewayId,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function mapLevel(string $status): string
    {
        return match (strtoupper($status)) {
            'COMPLETED' => GatewayStatusLevel::PAID->value,
            'PENDING', 'WAITING_FOR_CONFIRMATION', 'NEW' => GatewayStatusLevel::PENDING->value,
            'CANCELED', 'CANCELLED', 'REJECTED' => GatewayStatusLevel::FAILED->value,
            default => GatewayStatusLevel::UNKNOWN->value,
        };
    }
}
