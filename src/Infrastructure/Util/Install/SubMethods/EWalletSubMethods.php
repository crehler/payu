<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Infrastructure\Util\Install\SubMethods;

use Crehler\PaymentBundle\Infrastructure\Util\Lifecycle\Install\SubPaymentCustomFieldCreator;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetEntity;

final class EWalletSubMethods extends SubPaymentCustomFieldCreator
{
    public function config(CustomFieldSetEntity $customFieldSetEntity, string $paymentMethodId): ?array
    {
        if ($this->getCustomFieldId(paymentMethodId: $paymentMethodId, customFieldSetEntity: $customFieldSetEntity) !== null) {
            return null;
        }

        return [
            'customFieldSetId' => $customFieldSetEntity->getId(),
            'name' => self::PAYMENT_SUB_METHOD . $paymentMethodId,
            'type' => 'int',
            'config' => [
                'componentName' => 'sw-field',
                'type' => 'number',
                'numberType' => 'int',
                'customFieldType' => 'number',
                'customFieldPosition' => 1,
                'label' => [
                    'en-GB' => 'Selected PayU E-wallet payment',
                    'pl-PL' => 'Wybrana płatność PayU E-wallet',
                    'de-DE' => 'Ausgewählte PayU E-wallet-Zahlung',
                ],
            ],
            'allowCustomerWrite' => true,
        ];
    }
}
