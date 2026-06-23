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
use Crehler\PayU\Infrastructure\Handler\BlikHandler;

final class Blik extends ShopwarePaymentMethod
{
    public function __construct()
    {
        parent::__construct(
            handlerIdentifier: BlikHandler::class,
            position: 2,
            technicalName: PaymentMethodsEnum::BLIK_NAME->value,
            translations: [
                new ShopwarePaymentMethodDescription(
                    language: 'pl-PL',
                    name: 'BLIK',
                    description: 'Podaj kod wygenerowany w aplikacji banku. Obsługiwane przez PayU.'
                ),
                new ShopwarePaymentMethodDescription(
                    language: 'en-GB',
                    name: 'BLIK',
                    description: 'Enter the code generated in your bank app. Powered by PayU.'
                ),
                new ShopwarePaymentMethodDescription(
                    language: 'de-DE',
                    name: 'BLIK',
                    description: 'Geben Sie den in Ihrer Bank-App generierten Code ein. Powered by PayU.'
                ),
            ],
            afterOrderEnabled: true,
            iconName: 'blik',
        );
    }
}
