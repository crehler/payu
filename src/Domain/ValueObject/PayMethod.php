<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Domain\ValueObject;

use Crehler\PayU\Domain\Enum\PaymentMethodsEnum;

final readonly class PayMethod
{
    /**
     * @param string<PaymentMethodsEnum> $authorizationType
     * @param string<PaymentMethodsEnum> $type
     */
    public function __construct(
        public string $type,
        public string $value,
        public ?string $authorizationCode = null,
        public ?string $authorizationType = null,
        public ?int $amount = null,
    ) {
    }

    public function withAuthorizationCode(string $authorizationCode): self
    {
        return new self(
            type: $this->type,
            value: $this->value,
            authorizationCode: $authorizationCode,
            authorizationType: $this->authorizationType,
            amount: $this->amount,
        );
    }

    public function withAuthorizationType(string $authorizationType): self
    {
        return new self(
            type: $this->type,
            value: $this->value,
            authorizationCode: $this->authorizationCode,
            authorizationType: $authorizationType,
            amount: $this->amount,
        );
    }

    public function withFingerPrintDevice(string $fingerPrintDevice): self
    {
        return new self(
            type: $this->type,
            value: $this->value,
            authorizationCode: $this->authorizationCode,
            authorizationType: $this->authorizationType,
            amount: $this->amount,
        );
    }
}
