<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Subscriber;

use Crehler\PaymentBundle\Application\Service\{RefundSynchronizer, TransactionStateApplier};
use Crehler\PaymentBundle\Domain\Constant\PaymentCustomFields;
use Crehler\PaymentBundle\Domain\Event\PaymentNotificationReceivedEvent;
use Crehler\PaymentBundle\Domain\ValueObjects\{RefundStatus, TransactionStateTransition};
use Crehler\PaymentBundle\Infrastructure\Subscriber\AbstractPaymentNotificationSubscriber;
use Crehler\PaymentBundle\Shared\EnhancedLogger;
use Crehler\PayU\Application\DTO\OrderNotificationDTO;
use Crehler\PayU\Infrastructure\Port\PaymentGatewayPort;
use OpenPayU_Order;
use OpenPayuOrderStatus;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

use function json_decode;
use function strtoupper;

class PaymentNotificationSubscriber extends AbstractPaymentNotificationSubscriber
{
    /**
     * The verified notification for the event currently being processed.
     * Populated in verify() and consumed by resolveOrderTransaction()/mapStatus(),
     * which the bundle base always calls in that order within a single request.
     */
    private ?OrderNotificationDTO $notification = null;

    public function __construct(
        TransactionStateApplier $transactionStateApplier,
        EnhancedLogger $logger,
        private readonly PaymentGatewayPort $paymentGatewayPort,
        private readonly EntityRepository $orderRepository,
        private readonly RefundSynchronizer $refundSynchronizer,
    ) {
        parent::__construct($transactionStateApplier, $logger);
    }

    /**
     * PayU notifications carry the openpayu-signature header and an
     * order.orderId/order.status JSON body.
     */
    protected function supports(PaymentNotificationReceivedEvent $event): bool
    {
        $headers = $event->getHeaders();
        $payload = $event->getPayload();

        $hasSignature = isset($headers['openpayu-signature']);
        $hasOrderData = isset($payload['order']['orderId'], $payload['order']['status']);

        return $hasSignature && $hasOrderData;
    }

    /**
     * Verification = the OpenPayU SDK consuming the notification (signature check)
     * plus re-retrieving the order. The parsed result is stashed for the resolve
     * and map steps.
     */
    protected function verify(PaymentNotificationReceivedEvent $event): bool
    {
        $this->notification = null;

        $salesChannelId = $this->resolveSalesChannelIdFromRequest($event->request);

        $notification = $this->paymentGatewayPort->verifyNotification(
            request: $event->request,
            salesChannelId: $salesChannelId,
        );

        if (!$notification->status) {
            $this->logger->error('PayU notification verification failed', [
                'error' => $notification->error,
                'infrastructureError' => $notification->infrastructureError,
            ]);

            return false;
        }

        if (!$notification->orderId || !$notification->shopOrderId) {
            $this->logger->error('PayU notification missing orderId/shopOrderId', [
                'payuOrderId' => $notification->orderId,
            ]);

            return false;
        }

        if ($notification->orderStatus !== OpenPayU_Order::SUCCESS) {
            $this->logger->error('PayU order retrieval failed', [
                'orderStatus' => $notification->orderStatus,
            ]);

            return false;
        }

        $this->logger->info('PayU notification verified', [
            'payuOrderId' => $notification->orderId,
            'shopOrderId' => $notification->shopOrderId,
            'paymentStatus' => $notification->paymentStatus,
        ]);

        $this->notification = $notification;

        return true;
    }

    protected function resolveOrderTransaction(
        PaymentNotificationReceivedEvent $event,
        Context $context,
    ): ?OrderTransactionEntity {
        if ($this->notification === null || $this->notification->shopOrderId === null) {
            return null;
        }

        $order = $this->findOrder($this->notification->shopOrderId, $context);

        if ($order === null) {
            $this->logger->error('PayU order not found in Shopware', [
                'shopOrderId' => $this->notification->shopOrderId,
                'payuOrderId' => $this->notification->orderId,
            ]);

            return null;
        }

        $orderTransaction = $this->resolveTransactionForNotification($order);

        if ($orderTransaction === null) {
            $this->logger->error('PayU order has no transactions', [
                'shopOrderId' => $this->notification->shopOrderId,
                'orderId' => $order->getId(),
                'orderNumber' => $order->getOrderNumber(),
            ]);
        }

        return $orderTransaction;
    }

    protected function mapStatus(
        PaymentNotificationReceivedEvent $event,
        OrderTransactionEntity $orderTransaction,
    ): TransactionStateTransition {
        // A refund notification never drives the transaction transition here: the
        // RefundSynchronizer (beforeApply) owns both the refund-entity state and the
        // amount-aware transaction refund/partial-refund transition. Returning a
        // transition would double-apply it. PayU also keeps the order status COMPLETED
        // after a refund, so without this guard a refund webhook would re-map to PAID.
        if ($this->isRefundNotification()) {
            return TransactionStateTransition::NONE;
        }

        $paymentStatus = $this->notification?->paymentStatus;

        $this->logger->info('PayU processing payment status', [
            'transactionId' => $orderTransaction->getId(),
            'currentState' => $orderTransaction->getStateMachineState()?->getTechnicalName(),
            'payuPaymentStatus' => $paymentStatus,
        ]);

        return match ($paymentStatus) {
            OpenPayuOrderStatus::STATUS_COMPLETED => TransactionStateTransition::PAID,
            OpenPayuOrderStatus::STATUS_CANCELED,
            OpenPayuOrderStatus::STATUS_REJECTED => TransactionStateTransition::CANCELLED,
            default => TransactionStateTransition::NONE,
        };
    }

    /**
     * For a refund notification, sync it onto the native refund entity. This finalizes
     * an in-shop refund left PENDING by the provider (in_progress -> completed/failed and
     * the matching order-transaction transition) and records a refund made directly in the
     * PayU panel. Idempotent by gateway refund id (handled by the synchronizer).
     */
    protected function beforeApply(
        PaymentNotificationReceivedEvent $event,
        OrderTransactionEntity $orderTransaction,
        TransactionStateTransition $transition,
        Context $context,
    ): void {
        if (!$this->isRefundNotification()) {
            return;
        }

        $payuRefundStatus = strtoupper((string) ($this->notification?->refundStatus ?? ''));

        $status = match ($payuRefundStatus) {
            'FINALIZED' => RefundStatus::COMPLETED,
            'CANCELED' => RefundStatus::FAILED,
            default => RefundStatus::IN_PROGRESS, // PENDING
        };

        $this->logger->info('PayU refund notification', [
            'orderTransactionId' => $orderTransaction->getId(),
            'gatewayRefundId' => $this->notification?->refundId,
            'payuRefundStatus' => $payuRefundStatus,
            'refundAmount' => $this->notification?->refundAmount,
        ]);

        $this->refundSynchronizer->syncExternalRefund(
            orderTransactionId: $orderTransaction->getId(),
            amountMinor: $this->notification?->refundAmount,
            gatewayRefundId: $this->notification?->refundId,
            status: $status,
            context: $context,
        );
    }

    /**
     * Match the transaction carrying the notification's PayU order id (persisted
     * as the gateway payment id by the handlers). On retries or a payment-method
     * change an order has several transactions; picking the newest one blindly
     * can settle the wrong transaction. Falls back to the newest transaction for
     * legacy rows created before the gateway payment id was persisted.
     */
    private function resolveTransactionForNotification(OrderEntity $order): ?OrderTransactionEntity
    {
        $transactions = $order->getTransactions();

        if ($transactions === null) {
            return null;
        }

        $payuOrderId = $this->notification?->orderId;

        if ($payuOrderId !== null) {
            foreach ($transactions as $transaction) {
                if (($transaction->getCustomFields()[PaymentCustomFields::GATEWAY_PAYMENT_ID] ?? null) === $payuOrderId) {
                    return $transaction;
                }
            }
        }

        // transactions are sorted createdAt DESC in findOrder() -> first() is the newest.
        return $transactions->first();
    }

    private function isRefundNotification(): bool
    {
        return $this->notification?->refundStatus !== null && $this->notification?->refundStatus !== '';
    }

    private function resolveSalesChannelIdFromRequest(Request $request): ?string
    {
        try {
            $body = json_decode($request->getContent(), true);
            $extOrderId = $body['order']['extOrderId'] ?? null;

            if (!$extOrderId) {
                return null;
            }

            // extOrderId is the order transaction id (unique per payment attempt);
            // resolve the owning order's sales channel via transactions.id.
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('transactions.id', $extOrderId));
            $criteria->addFields(['salesChannelId']);

            $order = $this->orderRepository->search($criteria, Context::createDefaultContext())->first();

            if ($order === null) {
                // Legacy fallback: extOrderId used to be the order id.
                $legacy = new Criteria([$extOrderId]);
                $legacy->addFields(['salesChannelId']);
                $order = $this->orderRepository->search($legacy, Context::createDefaultContext())->first();
            }

            return $order?->get('salesChannelId');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Resolve the order owning the notification's extOrderId. Since extOrderId is the
     * order transaction id (unique per attempt), look the order up via transactions.id;
     * fall back to treating the value as the order id for legacy orders created before
     * the per-transaction extOrderId scheme. All transactions are loaded either way so
     * resolveTransactionForNotification() can pick the one matching the PayU order id.
     */
    private function findOrder(string $extOrderId, Context $context): ?OrderEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('transactions.id', $extOrderId));
        $this->withTransactionAssociations($criteria);

        $order = $this->orderRepository->search($criteria, $context)->first();

        if ($order !== null) {
            return $order;
        }

        $legacy = new Criteria([$extOrderId]);
        $this->withTransactionAssociations($legacy);

        return $this->orderRepository->search($legacy, $context)->first();
    }

    private function withTransactionAssociations(Criteria $criteria): void
    {
        $criteria->addAssociation('transactions.paymentMethod');
        $criteria->addAssociation('transactions.stateMachineState');
        $criteria->getAssociation('transactions')
            ->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
    }
}
