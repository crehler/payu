<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Infrastructure\Mapper;

use Crehler\PaymentBundle\Application\DTO\PaymentRequest\OrderItemDTO;
use Crehler\PaymentBundle\Application\Factory\PaymentRequestDtoFactory;
use Crehler\PaymentBundle\Application\Service\TransactionDescriptionRenderer;
use Crehler\PayU\Application\DTO\CreateOrder\OrderRequest;

use function array_map;

final readonly class PaymentRequestMapper
{
    private const CONFIG_DOMAIN = 'CrehlerPayU.config';

    public function __construct(
        private PaymentRequestDtoFactory $paymentRequestDtoFactory,
        private TransactionDescriptionRenderer $descriptionRenderer,
    ) {
    }

    public function mapOrderTransactionToPaymentRequest(OrderRequest $req): array
    {
        $order = $req->orderTransaction->order;
        $customer = $order->customer;

        $buyerData = [
            'email' => $customer->email,
            'firstName' => $customer->firstName,
            'lastName' => $customer->lastName,
        ];

        if ($customer->phone !== null && $customer->phone !== '') {
            $buyerData['phone'] = $customer->phone;
        }

        // Build items through the bundle factory and reconcile their summed total
        // against the order total (rounding can make sum(items) drift a few minor
        // units, which PayU rejects), then serialize to PayU's string product shape.
        $items = $this->paymentRequestDtoFactory->createOrderItems($order->lineItems);
        $items = $this->paymentRequestDtoFactory->reconcileItemsTotal(
            items: $items,
            expectedTotal: $req->orderTransaction->totalAmount->amount,
        );

        $products = array_map(
            static fn (OrderItemDTO $item): array => [
                'name' => $item->name,
                'quantity' => (string) $item->quantity,
                'unitPrice' => (string) $item->unitPrice,
            ],
            $items,
        );

        $result = [
            'notifyUrl' => $req->notifyUrl,
            'description' => $this->setDescription(req: $req),
            'merchantPosId' => $req->merchantPosId,
            'continueUrl' => $req->continueUrl,
            // Must be unique per PayU order: a retry (decline → "pay again") creates a
            // new order transaction for the same order, but reusing the order id as
            // extOrderId makes PayU reject the second create with ERROR_ORDER_NOT_UNIQUE.
            // The order transaction id is unique per attempt; the webhook resolves the
            // order back via transactions.id.
            'extOrderId' => $req->orderTransaction->id,
            'currencyCode' => $order->currencyCode ?? 'PLN',
            'totalAmount' => (string) $req->orderTransaction->totalAmount->amount,
            'buyer' => $buyerData,
            'products' => $products,
        ];

        // PayU uses customerIp for fraud scoring — send the real client IP when we
        // have it, but omit the field entirely rather than send a fake loopback that
        // would degrade the signal for every affected order.
        if ($req->customerIp !== null && $req->customerIp !== '') {
            $result['customerIp'] = $req->customerIp;
        }

        if ($req->payMethod->value !== '') {
            $payMethodPayload = [
                'type' => $req->payMethod->type,
                'value' => $req->payMethod->value,
            ];

            if ($req->payMethod->authorizationCode !== null) {
                $payMethodPayload['authorizationCode'] = $req->payMethod->authorizationCode;
            }

            if ($req->payMethod->authorizationType !== null) {
                $payMethodPayload['authorizationType'] = $req->payMethod->authorizationType;
            }

            $result['payMethods'] = ['payMethod' => $payMethodPayload];
        }

        return $result;
    }

    private function setDescription(OrderRequest $req): string
    {
        $order = $req->orderTransaction->order;

        return $this->descriptionRenderer->render(self::CONFIG_DOMAIN, $order, $order->salesChannelId);
    }
}
