<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Application\Port\Driven;

use Crehler\PayU\Application\DTO\PaymentMethodResponse;

interface PaymentMethodServicePort
{
    public function getPaymentMethods(object $paymentMethods, int $checkoutValue, string $shopwarePaymentId): PaymentMethodResponse;
}
