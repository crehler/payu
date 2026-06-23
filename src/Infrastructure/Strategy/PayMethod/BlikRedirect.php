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

class BlikRedirect implements PayMethodStrategy
{
    /**
     * @var string
     */
    public const BLIK = 'blik';

    public function createPayMethod(
        Customer $customer,
        PaymentMethod $paymentMethod,
        ?string $authorizationCode = null,
        ?string $fingerPrintDevice = null,
        ?string $paymentSubMethodId = null,
    ): PayMethod {
        return new PayMethod(
            type: PaymentMethodsEnum::PBL->value,
            value: self::BLIK
        );
    }

    public function supports(string $handlerIdentifier, ?string $authorizationCode = null, ?string $fingerPrintDevice = null): bool
    {
        return $handlerIdentifier === BlikHandler::class && $authorizationCode === null;
    }
}
