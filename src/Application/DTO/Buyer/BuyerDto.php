<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Application\DTO\Buyer;

final readonly class BuyerDto
{
    public function __construct(
        public string $email,
        public string $firstName,
        public string $lastName,
        public string $extCustomerId,
        public BuyerDeliverDTO $delivery,
        public ?string $phone = null,
        public ?string $language = null,
    ) {
    }
}
