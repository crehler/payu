<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Application\DTO;

use Crehler\PaymentBundle\Domain\ValueObjects\PaymentSubMethod;

final readonly class PaymentMethodResponse
{
    /**
     * @param array<PaymentSubMethod> $payByLinks
     */
    public function __construct(
        public string $status,
        public array $payByLinks,
        public array $savedCards,
    ) {
    }
}
