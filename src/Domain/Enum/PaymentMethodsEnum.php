<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Domain\Enum;

enum PaymentMethodsEnum: string
{
    case BANK_NAME = 'payu_bank';
    case CARD_NAME = 'payu_card';
    case BLIK_NAME = 'payu_blik';
    case E_WALLET_NAME = 'payu_ewallet';
    case DEFERRED_NAME = 'payu_deferred';

    case PBL = 'PBL';
    case CARD_TOKEN = 'CARD_TOKEN';
    case PAYMENT_WALL = 'PAYMENT_WALL';
    case BLIK_AUTHORIZATION_CODE = 'BLIK_AUTHORIZATION_CODE';
    case BLIK_TOKEN = 'BLIK_TOKEN';

    public static function paymentMethods(): array
    {
        return [
            self::BANK_NAME,
            self::CARD_NAME,
            self::BLIK_NAME,
            self::E_WALLET_NAME,
            self::DEFERRED_NAME,
        ];
    }
}
