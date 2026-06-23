<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Application\DTO;

final readonly class OrderNotificationDTO
{
    public function __construct(
        public bool $status,
        public ?string $orderId = null,
        public ?string $shopOrderId = null,
        public ?string $orderStatus = null,
        public ?string $paymentStatus = null,
        public ?object $notification = null,
        public ?string $error = null,
        public bool $infrastructureError = false,
        public ?string $refundId = null,
        public ?string $refundStatus = null,
        public ?int $refundAmount = null,
    ) {
    }
}
