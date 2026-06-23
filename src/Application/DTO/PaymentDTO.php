<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Application\DTO;

use Crehler\PaymentBundle\Domain\Entity\Order\Order;
use Crehler\PaymentBundle\Domain\Entity\OrderTransaction\OrderTransaction;

final readonly class PaymentDTO
{
    public function __construct(
        public Order $order,
        public OrderTransaction $orderTransaction,
        public string $continueUrl,
        public ?string $customerIp,
        public string $notifyUrl,
        public ?string $authorizeCode = null,
        public ?string $fingerPrintDevice = null,
        public ?string $salesChannelId = null,
        public ?string $paymentSubMethodId = null,
    ) {
    }
}
