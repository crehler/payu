<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Domain\ValueObject;

use function array_filter;
use function in_array;

final readonly class PaymentMethods
{
    /**
     * @var string
     */
    public const ENABLED = 'ENABLED';

    /**
     * @param array<PaymentMethod> $paymentMethods
     */
    public function __construct(
        public array $paymentMethods,
    ) {
    }

    /**
     * @return array<PaymentMethod>
     */
    public function getEnabledPaymentMethods(): array
    {
        return array_filter($this->paymentMethods, fn (PaymentMethod $paymentMethod) => $paymentMethod->status === self::ENABLED);
    }

    public function addPaymentMethod(PaymentMethod $paymentMethod): self
    {
        if (in_array($paymentMethod, $this->paymentMethods, true)) {
            return $this;
        }

        return new self([...$this->paymentMethods, $paymentMethod]);
    }
}
