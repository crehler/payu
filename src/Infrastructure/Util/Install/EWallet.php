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
use Crehler\PayU\Infrastructure\Handler\EWalletHandler;

final class EWallet extends ShopwarePaymentMethod
{
    public function __construct()
    {
        parent::__construct(
            handlerIdentifier: EWalletHandler::class,
            position: 3,
            technicalName: PaymentMethodsEnum::E_WALLET_NAME->value,
            translations: [
                new ShopwarePaymentMethodDescription(
                    language: 'pl-PL',
                    name: 'Portfel elektroniczny',
                    description: 'Płać za pomocą portfela elektronicznego. Obsługiwane przez PayU.'
                ),
                new ShopwarePaymentMethodDescription(
                    language: 'en-GB',
                    name: 'E-wallet',
                    description: 'Pay with your e-wallet. Powered by PayU.'
                ),
                new ShopwarePaymentMethodDescription(
                    language: 'de-DE',
                    name: 'E-Wallet',
                    description: 'Zahlen Sie mit Ihrem E-Wallet. Powered by PayU.'
                ),
            ],
            afterOrderEnabled: true,
            iconName: 'ewallet',
            subMethodsEnabled: true
        );
    }
}
