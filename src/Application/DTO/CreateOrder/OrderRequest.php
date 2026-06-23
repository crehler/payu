<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Application\DTO\CreateOrder;

use Crehler\PaymentBundle\Domain\Entity\OrderTransaction\OrderTransaction;
use Crehler\PayU\Domain\ValueObject\PayMethod;

final readonly class OrderRequest
{
    public function __construct(
        public PayMethod $payMethod,
        public OrderTransaction $orderTransaction,
        public string $notifyUrl,
        public ?string $customerIp,
        public string $continueUrl,
        public ?string $salesChannelId = null,
        public ?string $merchantPosId = null,
    ) {
    }

    public function withMerchantPosId(self $orderRequest, string $merchantPosId): self
    {
        return new self(
            payMethod: $orderRequest->payMethod,
            orderTransaction: $orderRequest->orderTransaction,
            notifyUrl: $orderRequest->notifyUrl,
            customerIp: $orderRequest->customerIp,
            continueUrl: $orderRequest->continueUrl,
            salesChannelId: $orderRequest->salesChannelId,
            merchantPosId: $merchantPosId
        );
    }
}
