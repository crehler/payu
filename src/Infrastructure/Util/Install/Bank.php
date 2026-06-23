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
use Crehler\PayU\Infrastructure\Handler\BankHandler;

final class Bank extends ShopwarePaymentMethod
{
    public function __construct()
    {
        parent::__construct(
            handlerIdentifier: BankHandler::class,
            position: 3,
            technicalName: PaymentMethodsEnum::BANK_NAME->value,
            translations: [
                new ShopwarePaymentMethodDescription(
                    language: 'pl-PL',
                    name: 'Przelew online',
                    description: 'Wybierz swój bank i dokonaj płatności. Obsługiwane przez PayU.'
                ),
                new ShopwarePaymentMethodDescription(
                    language: 'en-GB',
                    name: 'Online transfer',
                    description: 'Choose your bank and make a payment. Powered by PayU.'
                ),
                new ShopwarePaymentMethodDescription(
                    language: 'de-DE',
                    name: 'Online-Überweisung',
                    description: 'Wählen Sie Ihre Bank und führen Sie die Zahlung durch. Powered by PayU.'
                ),
            ],
            afterOrderEnabled: true,
            iconName: 'bank',
            subMethodsEnabled: true
        );
    }
}
