<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Application\DTO\CreateOrder;

use Symfony\Component\HttpFoundation\Response;

final readonly class OrderResponse
{
    public function __construct(
        public bool $status,
        public int $code,
        public ?string $orderId = null,
        public ?string $redirectUri = null,
        public ?string $error = null,
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->status === true && $this->code === Response::HTTP_OK;
    }
}
