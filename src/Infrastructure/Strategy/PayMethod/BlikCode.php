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
use Crehler\PayU\Domain\Enum\PaymentMethodsEnum;
use Crehler\PayU\Domain\ValueObject\PayMethod;
use Crehler\PayU\Infrastructure\Handler\BlikHandler;

use function is_null;

class BlikCode implements PayMethodStrategy
{
    public function createPayMethod(
        Customer $customer,
        PaymentMethod $paymentMethod,
        ?string $authorizationCode = null,
        ?string $fingerPrintDevice = null,
        ?string $paymentSubMethodId = null,
    ): PayMethod {
        return new PayMethod(
            type: PaymentMethodsEnum::BLIK_AUTHORIZATION_CODE->value,
            value: $authorizationCode
        );
    }

    public function supports(string $handlerIdentifier, ?string $authorizationCode = null, ?string $fingerPrintDevice = null): bool
    {
        return $handlerIdentifier === BlikHandler::class && !is_null($authorizationCode);
    }
}
