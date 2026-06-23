<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Infrastructure\Port;

use Crehler\PayU\Application\DTO\CreateOrder\{OrderRequest, OrderResponse};
use Crehler\PayU\Application\DTO\{OrderNotificationDTO, PaymentMethodResponse};
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Driven port over the PayU gateway. It lives in Infrastructure (not Domain)
 * because it works in HTTP/DAL terms — it takes a Symfony Request and a Shopware
 * CustomerEntity — so keeping it here keeps the Domain framework-free.
 */
#[AutoconfigureTag]
interface PaymentGatewayPort
{
    public function getAvailablePaymentMethods(
        string $paymentMethodId,
        ?CustomerEntity $customerEntity = null,
        string $lang = 'pl',
        int $checkoutValue = 0,
        string $salesChannelId = '',
    ): PaymentMethodResponse;

    public function createOrder(OrderRequest $request): OrderResponse;

    /**
     * Server-side ownership check for a reusable PayU card token: re-fetches the
     * customer's saved tokens (trusted-merchant scope) for the given sales channel
     * and confirms the submitted token belongs to that customer. The storefront
     * value is never trusted as a bearer.
     */
    public function customerOwnsCardToken(
        string $token,
        string $customerId,
        string $customerEmail,
        ?string $salesChannelId = null,
    ): bool;

    public function verifyNotification(Request $request, ?string $salesChannelId = null): OrderNotificationDTO;
}
