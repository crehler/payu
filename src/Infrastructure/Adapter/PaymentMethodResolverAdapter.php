<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Infrastructure\Adapter;

use Crehler\PaymentBundle\Domain\Entity\Customer;
use Crehler\PaymentBundle\Domain\Entity\OrderTransaction\PaymentMethod;
use Crehler\PaymentBundle\Domain\Exception\PaymentStrategyNotFoundException;
use Crehler\PayU\Application\Port\Driven\PaymentMethodResolverPort;
use Crehler\PayU\Domain\ValueObject\PayMethod;
use Crehler\PayU\Infrastructure\Strategy\PayMethod\PayMethodStrategy;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class PaymentMethodResolverAdapter implements PaymentMethodResolverPort
{
    public function __construct(
        #[AutowireIterator(PayMethodStrategy::class)]
        private iterable $payMethodStrategies,
    ) {
    }

    public function resolve(
        Customer $customer,
        PaymentMethod $paymentMethod,
        ?string $authorizationCode = null,
        ?string $fingerPrintDevice = null,
        ?string $paymentSubMethodId = null,
    ): PayMethod {
        /** @var PayMethodStrategy $payMethodStrategy */
        foreach ($this->payMethodStrategies as $payMethodStrategy) {
            if ($payMethodStrategy->supports(
                handlerIdentifier: $paymentMethod->handlerIdentifier,
                authorizationCode: $authorizationCode,
                fingerPrintDevice: $fingerPrintDevice
            )) {
                return $payMethodStrategy->createPayMethod(
                    customer: $customer,
                    paymentMethod: $paymentMethod,
                    authorizationCode: $authorizationCode,
                    fingerPrintDevice: $fingerPrintDevice,
                    paymentSubMethodId: $paymentSubMethodId,
                );
            }
        }

        throw new PaymentStrategyNotFoundException(handlerIdentifier: $paymentMethod->handlerIdentifier);
    }
}
