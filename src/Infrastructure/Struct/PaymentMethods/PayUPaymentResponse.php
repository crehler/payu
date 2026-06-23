<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Infrastructure\Struct\PaymentMethods;

use Crehler\PaymentBundle\Struct\Card\SavedCardCollection;
use Shopware\Core\Framework\Struct\Struct;

class PayUPaymentResponse extends Struct
{
    public function __construct(
        public readonly object $status,
        public readonly SavedCardCollection $cardTokens,
        public readonly PaymentMethodCollection $payByLinks,
    ) {
    }

    public function getApiAlias(): string
    {
        return 'cr_payu_payment_response';
    }
}
