<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Domain\ValueObject;

final readonly class PaymentMethod
{
    public function __construct(
        public string $value,
        public string $brandImageUrl,
        public string $name,
        public string $status,
        public ?int $minAmount = null,
        public ?int $maxAmount = null,
    ) {
    }

    public function withMinAmount(self $paymentMethod, int $minAmount): self
    {
        return new self(
            value: $paymentMethod->value,
            brandImageUrl: $paymentMethod->brandImageUrl,
            name: $paymentMethod->name,
            status: $paymentMethod->status,
            minAmount: $minAmount,
            maxAmount: $paymentMethod->maxAmount,
        );
    }

    public function withMaxAmount(self $paymentMethod, int $maxAmount): self
    {
        return new self(
            value: $paymentMethod->value,
            brandImageUrl: $paymentMethod->brandImageUrl,
            name: $paymentMethod->name,
            status: $paymentMethod->status,
            minAmount: $paymentMethod->minAmount,
            maxAmount: $maxAmount,
        );
    }
}
