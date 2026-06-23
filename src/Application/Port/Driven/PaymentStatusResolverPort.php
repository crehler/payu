<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Application\Port\Driven;

use Crehler\PayU\Application\DTO\CreateOrder\OrderResponse;
use OpenPayU_Result;

interface PaymentStatusResolverPort
{
    public function resolve(OpenPayU_Result $response, string $orderId): OrderResponse;
}
