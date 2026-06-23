<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Infrastructure\Util\Install;

use Crehler\PaymentBundle\Domain\Enum\CurrencyEnum;
use Crehler\PaymentBundle\Domain\Port\PaymentGatewayCurrencyProviderInterface;

final readonly class SupportCurrency implements PaymentGatewayCurrencyProviderInterface
{
    /**
     * @var string
     */
    public const ID = '01980e4c04ae715f8d9041eb4f8962cd';
    /**
     * @var string
     */
    public const PAYU = 'payu';

    public function getGatewayIdentifier(): string
    {
        return self::PAYU;
    }

    public function getRuleId(): string
    {
        return self::ID;
    }

    public function getSupportedCurrencyIsoCodes(): array
    {
        return [
            CurrencyEnum::PLN->value,
            CurrencyEnum::EUR->value,
            CurrencyEnum::USD->value,
        ];
    }

    public function getTranslations(): array
    {
        return [
            'en-GB' => [
                'name' => 'PayU - Supported currencies',
                'description' => 'This rule was automatically added by the PayU payment plugin, it represents all currencies supported by PayU and should be assigned to PayU payment methods.',
            ],
            'pl-PL' => [
                'name' => 'PayU - Obsługiwane waluty',
                'description' => 'Ta reguła została automatycznie dodana przez wtyczkę do obsługi płatności PayU, reprezentuje wszystkie waluty obsługiwane przez PayU i powinna być przypisana do metod płatności PayU.',
            ],
            'de-DE' => [
                'name' => 'PayU - Unterstützte Währungen',
                'description' => 'Diese Regel wurde automatisch vom PayU-Zahlungs-Plugin hinzugefügt. Sie repräsentiert alle von PayU unterstützten Währungen und sollte den PayU-Zahlungsmethoden zugewiesen werden.',
            ],
        ];
    }
}
