<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Infrastructure\Strategy\PayMethod;

use Crehler\PaymentBundle\Application\Port\Driven\CustomerPaymentSubMethodRepositoryPort;
use Crehler\PaymentBundle\Domain\Entity\Customer;
use Crehler\PaymentBundle\Domain\Entity\OrderTransaction\PaymentMethod;
use Crehler\PayU\Domain\Enum\PaymentMethodsEnum;
use Crehler\PayU\Domain\ValueObject\PayMethod;
use Crehler\PayU\Infrastructure\Handler\EWalletHandler;

final class EWallet implements PayMethodStrategy
{
    public function __construct(
        private CustomerPaymentSubMethodRepositoryPort $customerPaymentSubMethodRepositoryPort,
    ) {
    }

    public function createPayMethod(
        Customer $customer,
        PaymentMethod $paymentMethod,
        ?string $authorizationCode = null,
        ?string $fingerPrintDevice = null,
        ?string $paymentSubMethodId = null,
    ): PayMethod {
        $customerSelectedMethod = $this->customerPaymentSubMethodRepositoryPort->get(
            customer: $customer,
            paymentMethodId: $paymentMethod->id
        );

        $value = $customerSelectedMethod?->subPaymentMethodId
            ?? $paymentSubMethodId
            ?? '';

        return new PayMethod(
            type: PaymentMethodsEnum::PBL->value,
            value: $value
        );
    }

    public function supports(string $handlerIdentifier, ?string $authorizationCode = null, ?string $fingerPrintDevice = null): bool
    {
        return $handlerIdentifier === EWalletHandler::class;
    }
}
