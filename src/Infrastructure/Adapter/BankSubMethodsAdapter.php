<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Infrastructure\Adapter;

use Crehler\PaymentBundle\Domain\ValueObjects\PaymentSubMethod;
use Crehler\PaymentBundle\Infrastructure\Provider\{AbstractPaymentSubMethodProvider, RawSubMethod};
use Crehler\PayU\Infrastructure\Handler\BankHandler;
use Crehler\PayU\Infrastructure\Port\PaymentGatewayPort;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

use function array_merge;
use function in_array;

final class BankSubMethodsAdapter extends AbstractPaymentSubMethodProvider
{
    public function __construct(
        private readonly PaymentGatewayPort $paymentGatewayPort,
    ) {
    }

    public function supportsPaymentMethod(PaymentMethodEntity $paymentMethodEntity): bool
    {
        return $paymentMethodEntity->getHandlerIdentifier() === BankHandler::class;
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

        // Bank pay-by-links are everything except the e-wallet and deferred groups,
        // which have their own handlers.
        $excludedMethods = array_merge(
            EWalletSubMethodsAdapter::E_WALLET_PAYMENT_METHODS,
            DeferredSubMethodsAdapter::DEFERRED_PAYMENT_METHODS
        );

        foreach ($paymentMethods->payByLinks as $payByLink) {
            if (in_array($payByLink->providerId, $excludedMethods, true)) {
                continue;
            }

            yield $this->toRawSubMethod($payByLink);
        }
    }

    private function toRawSubMethod(PaymentSubMethod $subMethod): RawSubMethod
    {
        return new RawSubMethod(
            providerId: $subMethod->providerId,
            name: $subMethod->name,
            mediaUrl: $subMethod->mediaUrl,
            minAmount: $subMethod->minAmount,
            maxAmount: $subMethod->maxAmount,
        );
    }
}
