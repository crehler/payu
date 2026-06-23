<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Application\Port\Driven;

use Crehler\PaymentBundle\Domain\Entity\Customer;
use Crehler\PaymentBundle\Domain\Entity\OrderTransaction\PaymentMethod;
use Crehler\PayU\Domain\ValueObject\PayMethod;

interface PaymentMethodResolverPort
{
    public function resolve(
        Customer $customer,
        PaymentMethod $paymentMethod,
        ?string $authorizationCode = null,
        ?string $fingerPrintDevice = null,
        ?string $paymentSubMethodId = null,
    ): PayMethod;
}
