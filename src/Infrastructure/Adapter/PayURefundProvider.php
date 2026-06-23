<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Infrastructure\Adapter;

use Crehler\PaymentBundle\Application\DTO\Refund\RefundCommand;
use Crehler\PaymentBundle\Application\Port\Driven\RefundProviderPort;
use Crehler\PaymentBundle\Application\Service\OrderTransactionSalesChannelResolver;
use Crehler\PaymentBundle\Domain\ValueObjects\RefundResult;
use Crehler\PaymentBundle\Shared\EnhancedLogger;
use Crehler\PayU\Infrastructure\Client\{PayUClient, PayUClientFactory};
use Crehler\PayU\Infrastructure\Handler\{BankHandler, BlikHandler, CardHandler, DeferredHandler, EWalletHandler};
use Shopware\Core\Framework\Context;
use Throwable;

use function in_array;
use function sprintf;
use function strtoupper;

/**
 * PayU implementation of the bundle's refund port. Translates a gateway-agnostic
 * RefundCommand into an OpenPayU refund call and maps the response to a RefundResult.
 * All native-entity / state-machine handling lives in the bundle handler; this class
 * only configures the SDK for the right sales channel and performs the gateway call.
 */
final readonly class PayURefundProvider implements RefundProviderPort
{
    private const SUPPORTED_HANDLERS = [
        BankHandler::class,
        BlikHandler::class,
        CardHandler::class,
        DeferredHandler::class,
        EWalletHandler::class,
    ];

    public function __construct(
        private PayUClientFactory $payUClientFactory,
        private PayUClient $payUClient,
        private OrderTransactionSalesChannelResolver $salesChannelResolver,
        private EnhancedLogger $logger,
    ) {
    }

    public function supports(string $handlerIdentifier): bool
    {
        return in_array($handlerIdentifier, self::SUPPORTED_HANDLERS, true);
    }

    public function refund(RefundCommand $command, Context $context): RefundResult
    {
        $salesChannelId = $this->salesChannelResolver->resolve($command->orderTransactionId, $context);

        // Push the matching credential set into the global OpenPayU SDK config.
        $this->payUClientFactory->create($salesChannelId);

        // PayU requires a non-empty description; amount stays in minor units (grosze).
        $description = $command->reason !== null && $command->reason !== ''
            ? $command->reason
            : 'Shopware refund';

        try {
            $result = $this->payUClient->createRefund(
                $command->gatewayPaymentId,
                $description,
                $command->amount,
            );
        } catch (Throwable $e) {
            $this->logger->error('PayU refund API call failed', [
                'gatewayPaymentId' => $command->gatewayPaymentId,
                'orderTransactionId' => $command->orderTransactionId,
                'exception' => $e->getMessage(),
            ]);

            return RefundResult::failed($e->getMessage());
        }

        $statusCode = (string) $result->getStatus();
        $response = (array) $result->getResponse();
        $refund = (array) ($response['refund'] ?? []);
        $gatewayRefundId = isset($refund['refundId']) ? (string) $refund['refundId'] : null;
        $refundStatus = strtoupper((string) ($refund['status'] ?? ''));

        $this->logger->info('PayU refund response', [
            'orderTransactionId' => $command->orderTransactionId,
            'gatewayPaymentId' => $command->gatewayPaymentId,
            'statusCode' => $statusCode,
            'refundStatus' => $refundStatus,
            'refundId' => $gatewayRefundId,
        ]);

        if ($statusCode !== 'SUCCESS') {
            return RefundResult::failed(sprintf('PayU rejected the refund request (%s)', $statusCode));
        }

        // PayU finalizes refunds asynchronously: a freshly created refund is PENDING and
        // is later FINALIZED (or CANCELED) — the completion arrives via webhook, which the
        // bundle's RefundSynchronizer maps onto the refund entity. So we only report the
        // accepted-but-pending refund as IN_PROGRESS here.
        return match ($refundStatus) {
            'FINALIZED' => RefundResult::completed($gatewayRefundId),
            'CANCELED' => RefundResult::failed('PayU canceled the refund'),
            default => RefundResult::inProgress($gatewayRefundId),
        };
    }
}
