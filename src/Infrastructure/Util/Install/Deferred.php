<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Infrastructure\Util\Install;

use Crehler\PaymentBundle\Infrastructure\Util\Lifecycle\{ShopwarePaymentMethod, ShopwarePaymentMethodDescription};
use Crehler\PayU\Domain\Enum\PaymentMethodsEnum;
use Crehler\PayU\Infrastructure\Handler\DeferredHandler;

final class Deferred extends ShopwarePaymentMethod
{
    public function __construct()
    {
        parent::__construct(
            handlerIdentifier: DeferredHandler::class,
            position: 4,
            technicalName: PaymentMethodsEnum::DEFERRED_NAME->value,
            translations: [
                new ShopwarePaymentMethodDescription(
                    language: 'pl-PL',
                    name: 'Odroczone',
                    description: 'Kup teraz, zapłać później. Obsługiwane przez PayU.'
                ),
                new ShopwarePaymentMethodDescription(
                    language: 'en-GB',
                    name: 'Deferred',
                    description: 'Buy now, pay later. Powered by PayU.'
                ),
                new ShopwarePaymentMethodDescription(
                    language: 'de-DE',
                    name: 'Aufgeschoben',
                    description: 'Jetzt kaufen, später bezahlen. Powered by PayU.'
                ),
            ],
            afterOrderEnabled: true,
            iconName: 'deferred',
            subMethodsEnabled: true
        );
    }
}
