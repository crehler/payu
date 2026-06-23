<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Infrastructure\Resolver;

use Crehler\PaymentBundle\Domain\Entity\Customer;
use Crehler\PayU\Domain\ValueObject\{PayMethod, PaymentMethod};
use Crehler\PayU\Infrastructure\Strategy\PayMethod\PayMethodStrategy;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class PaymentMethodValueResolver
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
    ): PayMethod {
        /** @var PayMethodStrategy $payMethodStrategy */
        foreach ($this->payMethodStrategies as $payMethodStrategy) {
            if ($payMethodStrategy->supports(
                handlerIdentifier: $paymentMethod->getHandlerIdentifier(),
                authorizationCode: $authorizationCode,
                fingerPrintDevice: $fingerPrintDevice
            )) {
                return $payMethodStrategy->createPayMethod(
                    customer: $customer,
                    paymentMethod: $paymentMethod,
                    authorizationCode: $authorizationCode,
                    fingerPrintDevice: $fingerPrintDevice
                );
            }
        }

        throw new InvalidArgumentException('No payment method strategy found for handler: ' . $paymentMethod->getHandlerIdentifier());
    }
}
