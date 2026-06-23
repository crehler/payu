<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Infrastructure\Adapter;

use Crehler\PaymentBundle\Infrastructure\Port\SavedCardTokenProvider;
use Crehler\PayU\Infrastructure\Handler\CardHandler;
use Crehler\PayU\Infrastructure\Port\PaymentGatewayPort;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

final readonly class SavedCardTokensAdapter implements SavedCardTokenProvider
{
    public function __construct(
        private PaymentGatewayPort $paymentGateway,
    ) {
    }

    public function supportsPaymentMethod(PaymentMethodEntity $paymentMethod): bool
    {
        return $paymentMethod->getHandlerIdentifier() === CardHandler::class;
    }

    public function getCustomerCardTokens(PaymentMethodEntity $paymentMethod, SalesChannelContext $salesChannelContext): array
    {
        $paymentMethods = $this->paymentGateway->getAvailablePaymentMethods(
            paymentMethodId: $paymentMethod->getId(),
            customerEntity: $salesChannelContext->getCustomer(),
            salesChannelId: $salesChannelContext->getSalesChannelId(),
        );

        return $paymentMethods->savedCards;
    }
}
