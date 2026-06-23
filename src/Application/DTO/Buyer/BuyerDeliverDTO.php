<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Application\DTO\Buyer;

final readonly class BuyerDeliverDTO
{
    public function __construct(
        public string $street,
        public string $postalCode,
        public string $city,
        public string $countryCode,
        public ?string $state = null,
        public ?string $postalBox = null,
        public ?string $name = null,
        public ?string $recipientName = null,
        public ?string $recipientEmail = null,
        public ?string $recipientPhone = null,
    ) {
    }
}
