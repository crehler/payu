<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Infrastructure\Strategy\PayMethod;

use Crehler\PaymentBundle\Domain\Entity\Customer;
use Crehler\PaymentBundle\Domain\Entity\OrderTransaction\PaymentMethod;
use Crehler\PayU\Domain\ValueObject\PayMethod;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface PayMethodStrategy
{
    public function createPayMethod(
        Customer $customer,
        PaymentMethod $paymentMethod,
        ?string $authorizationCode = null,
        ?string $fingerPrintDevice = null,
        ?string $paymentSubMethodId = null,
    ): PayMethod;

    public function supports(
        string $handlerIdentifier,
        ?string $authorizationCode = null,
        ?string $fingerPrintDevice = null,
    ): bool;
}
