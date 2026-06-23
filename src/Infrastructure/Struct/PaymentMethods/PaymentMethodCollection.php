<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Infrastructure\Struct\PaymentMethods;

use Shopware\Core\Framework\Struct\Collection;

class PaymentMethodCollection extends Collection
{
    /**
     * @var string
     */
    private const ENABLED_STATUS = 'ENABLED';

    public function add($element): void
    {
        if (!$element instanceof PaymentMethodStruct) {
            return;
        }

        if (!$this->isEnabled($element)) {
            return;
        }

        parent::add($element);
    }

    public function isEnabled(PaymentMethodStruct $element): bool
    {
        return $element->getStatus() === self::ENABLED_STATUS;
    }

    public function getApiAlias(): string
    {
        return 'cr_payu_payment_method_collection';
    }
}
