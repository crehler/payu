<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Infrastructure\Struct\PaymentMethods;

use Shopware\Core\Framework\Struct\Struct;

class PaymentMethodStruct extends Struct
{
    public function __construct(
        public readonly string $value,
        public readonly string $brandImageUrl,
        public readonly string $name,
        public readonly string $status,
        public readonly ?int $minAmount = null,
        public readonly ?int $maxAmount = null,
    ) {
    }

    public function getApiAlias(): string
    {
        return 'cr_payu_payment_method';
    }
}
