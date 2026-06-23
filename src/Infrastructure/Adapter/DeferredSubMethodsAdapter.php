<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Infrastructure\Adapter;

use Crehler\PaymentBundle\Infrastructure\Provider\{AbstractPaymentSubMethodProvider, RawSubMethod};
use Crehler\PayU\Infrastructure\Handler\DeferredHandler;
use Crehler\PayU\Infrastructure\Port\PaymentGatewayPort;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

use function in_array;

final class DeferredSubMethodsAdapter extends AbstractPaymentSubMethodProvider
{
    /**
     * @var mixed[]
     */
    public const DEFERRED_PAYMENT_METHODS = ['ai', 'dpkl', 'dpp', 'dpt', 'ppf', 'dpcz', 'dpts'];

    public function __construct(
        private readonly PaymentGatewayPort $paymentGatewayPort,
    ) {
    }

    public function supportsPaymentMethod(PaymentMethodEntity $paymentMethodEntity): bool
    {
        return $paymentMethodEntity->getHandlerIdentifier() === DeferredHandler::class;
    }

    protected function fetchRawSubMethods(
        PaymentMethodEntity $paymentMethodEntity,
        int $paymentValue,
        SalesChannelContext $context,
    ): iterable {
        $paymentMethods = $this->paymentGatewayPort->getAvailablePaymentMethods(
            paymentMethodId: $paymentMethodEntity->getId(),
            checkoutValue: $paymentValue,
            salesChannelId: $context->getSalesChannel()->getId(),
        );

        foreach ($paymentMethods->payByLinks as $payByLink) {
            if (!in_array($payByLink->providerId, self::DEFERRED_PAYMENT_METHODS, true)) {
                continue;
            }

            yield new RawSubMethod(
                providerId: $payByLink->providerId,
                name: $payByLink->name,
                mediaUrl: $payByLink->mediaUrl,
                minAmount: $payByLink->minAmount,
                maxAmount: $payByLink->maxAmount,
            );
        }
    }
}
