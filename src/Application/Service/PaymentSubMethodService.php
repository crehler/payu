<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Application\Service;

use Crehler\PaymentBundle\Domain\ValueObjects\{PaymentSubMethod, SavedCard};
use Crehler\PayU\Application\DTO\PaymentMethodResponse;
use Crehler\PayU\Application\Port\Driven\PaymentMethodServicePort;

use function property_exists;

final readonly class PaymentSubMethodService implements PaymentMethodServicePort
{
    public function getPaymentMethods(
        object $paymentMethods,
        int $checkoutValue,
        string $shopwarePaymentId,
    ): PaymentMethodResponse {
        return new PaymentMethodResponse(
            status: $paymentMethods->status->statusCode,
            payByLinks: $this->createPaymentSubMethods(
                paymentMethods: $paymentMethods,
                shopwarePaymentId: $shopwarePaymentId,
                checkoutValue: $checkoutValue,
            ),
            savedCards: $this->createSavedCardsTokens(paymentMethods: $paymentMethods)
        );
    }

    private function createPaymentSubMethods(
        object $paymentMethods,
        string $shopwarePaymentId,
        int $checkoutValue,
    ): array {
        $paymentSubMethods = [];

        if (property_exists($paymentMethods, 'payByLinks') === false) {
            return $paymentSubMethods;
        }

        foreach ($paymentMethods->payByLinks as $method) {
            if (property_exists($method, 'minAmount')
                && $method?->minAmount !== null
                && $method->minAmount > $checkoutValue
            ) {
                continue;
            }

            $paymentSubMethods[] = new PaymentSubMethod(
                providerId: $method->value,
                name: $method->name,
                shopwareId: $shopwarePaymentId,
                mediaUrl: $method->brandImageUrl,
                minAmount: $method?->minAmount ?? null,
                maxAmount: $method?->maxAmount ?? null
            );
        }

        return $paymentSubMethods;
    }

    private function createSavedCardsTokens(object $paymentMethods): array
    {
        $savedCards = [];
        $cardTokens = $paymentMethods->cardTokens;

        if (empty($cardTokens)) {
            return $savedCards;
        }

        foreach ($cardTokens as $cardToken) {
            $savedCards[] = new SavedCard(
                token: $cardToken->value,
                brandImgUrl: $cardToken->brandImageUrl,
                status: $cardToken->status,
                expirationYear: $cardToken->cardExpirationYear,
                expirationMonth: $cardToken->cardExpirationMonth,
                cardNumberMasked: $cardToken->cardNumberMasked,
                cardBrand: $cardToken->cardBrand,
                preferred: $cardToken->preferred,
                cardScheme: $cardToken->cardScheme,
            );
        }

        return $savedCards;
    }
}
