<?php

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

declare(strict_types=1);

namespace Crehler\PayU\Test\Unit\Infrastructure\Adapter;

use Crehler\PaymentBundle\Domain\Entity\OrderTransaction\OrderTransaction;
use Crehler\PaymentBundle\Shared\EnhancedLogger;
use Crehler\PayU\Application\DTO\CreateOrder\{OrderRequest, OrderResponse};
use Crehler\PayU\Application\Port\Driven\{PaymentMethodServicePort, PaymentStatusResolverPort};
use Crehler\PayU\Domain\ValueObject\{PayMethod, PaymentMethod};
use Crehler\PayU\Infrastructure\Adapter\PaymentGatewayAdapter;
use Crehler\PayU\Infrastructure\Client\PayUClient;
use Crehler\PayU\Infrastructure\Util\Install\Bank;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class PaymentGatewayAdapterTest extends TestCase
{
    private MockObject|SystemConfigService $configService;
    private MockObject|EnhancedLogger $logger;
    private MockObject|PaymentStatusResolverPort $paymentStatusResolverPort;
    private MockObject|PaymentMethodServicePort $paymentMethodServicePort;
    private MockObject|PayUClient $payUClient;
    private PaymentGatewayAdapter $paymentGatewayAdapter;

    protected function setUp(): void
    {
        $this->configService = $this->createMock(SystemConfigService::class);
        $this->logger = $this->createMock(EnhancedLogger::class);
        $this->paymentStatusResolverPort = $this->createMock(PaymentStatusResolverPort::class);
        $this->paymentMethodServicePort = $this->createMock(PaymentMethodServicePort::class);
        $this->payUClient = $this->createMock(PayUClient::class);

        $this->paymentGatewayAdapter = new PaymentGatewayAdapter(
            $this->configService,
            $this->logger,
            $this->paymentStatusResolverPort,
            $this->paymentMethodServicePort,
            $this->payUClient
        );
    }

    public function testCreateOrderSuccess(): void
    {
        $payMethod = new PayMethod(
            type: 'PBL',
            value: 'c',
            amount: 100,
        );

        $paymentMethod = new PaymentMethod(
            id: 'test-payment-method-id',
            handlerIdentifier: Bank::class,
            active: true,
            technicalName: 'test-payment-method-technical-name',
        );

        // $orderRequest = new OrderRequest();

        $this->paymentGatewayAdapter->createOrder($orderRequest);
    }

    /*     public function testCreateOrderSuccess(): void
        {
            $orderTransaction = new OrderTransactionEntity();
            $orderTransaction->setOrderId('test-order-id');
            $order = new OrderEntity();
            $order->setId('test-order-id');
            $orderTransaction->setOrder($order);

            $orderRequest = new OrderRequest(
                orderTransaction: $orderTransaction,
                returnUrl: 'http://test.com/return',
                salesChannelId: 'test-sales-channel-id',
                customerIp: '127.0.0.1',
                totalAmount: 1000,
                currencyIsoCode: 'PLN',
                products: [],
                customer: null,
                shippingAddress: null,
                billingAddress: null
            );

            $payUResponse = new \stdClass();
            $payUResponse->status = (object)['statusCode' => 'SUCCESS'];
            $payUResponse->redirectUri = 'http://payu.com/redirect';
            $payUResponse->orderId = 'payu-order-id';

            $expectedResponse = new OrderResponse(true, 200, 'http://payu.com/redirect', 'payu-order-id');

            // Configure mocks
            $this->configService->method('get')->willReturn(true); // Assume sandbox is enabled and config is present
            $this->payUClient->method('getMerchantPosId')->willReturn('test-pos-id');
            $this->payUClient->method('createOrder')->willReturn($payUResponse);
            $this->paymentStatusResolverPort->method('resolve')->willReturn($expectedResponse);

            // Call the method to test
            $actualResponse = $this->paymentGatewayAdapter->createOrder($orderRequest);

            // Assert the results
            self::assertEquals($expectedResponse, $actualResponse);
            self::assertTrue($actualResponse->status);
            self::assertEquals('http://payu.com/redirect', $actualResponse->redirectUrl);
        } */
}
