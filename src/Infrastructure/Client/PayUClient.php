<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Infrastructure\Client;

use OauthGrantType;
use OpenPayU_Configuration;
use OpenPayU_Exception;
use OpenPayU_Exception_Network;
use OpenPayU_Order;
use OpenPayU_Refund;
use OpenPayU_Result;
use OpenPayU_Retrieve;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class PayUClient
{
    /**
     * @param array<string, mixed> $data
     */
    public function createOrder(array $data): OpenPayU_Result
    {
        return OpenPayU_Order::create($data);
    }

    public function getMerchantPosId(): string
    {
        return OpenPayU_Configuration::getMerchantPosId();
    }

    /**
     * Create a refund for a PayU order. $amount is in minor units (grosze);
     * pass null for a full refund. Requires the SDK configuration to be
     * initialized first (PayUClientFactory::create()).
     *
     * @throws OpenPayU_Exception
     */
    public function createRefund(string $orderId, string $description, ?int $amount = null): OpenPayU_Result
    {
        return OpenPayU_Refund::create($orderId, $description, $amount);
    }

    /**
     * @throws OpenPayU_Exception_Network
     */
    public function retrievePayMethods(string $lang = 'pl'): object
    {
        return OpenPayU_Retrieve::payMethods(lang: $lang)->getResponse();
    }

    public function consumeNotification(Request $request): OpenPayU_Result
    {
        return OpenPayU_Order::consumeNotification($request->getContent());
    }

    public function retrieveOrder(string $orderId): OpenPayU_Result
    {
        return OpenPayU_Order::retrieve($orderId);
    }

    /**
     * @throws Throwable
     */
    public function initializeConfiguration(
        string $environment,
        string $grantType,
        string $posId,
        string $signatureKey,
        string $oauthClientId,
        string $oauthClientSecret,
    ): void {
        OpenPayU_Configuration::setEnvironment($environment);
        OpenPayU_Configuration::setOauthGrantType($grantType);
        OpenPayU_Configuration::setMerchantPosId($posId);
        OpenPayU_Configuration::setSignatureKey($signatureKey);
        OpenPayU_Configuration::setOauthClientId($oauthClientId);
        OpenPayU_Configuration::setOauthClientSecret($oauthClientSecret);
    }

    public function setTrustedMerchant(string $customerId, string $customerEmail): void
    {
        OpenPayU_Configuration::setOauthGrantType(OauthGrantType::TRUSTED_MERCHANT);
        OpenPayU_Configuration::setOauthExtCustomerId($customerId);
        OpenPayU_Configuration::setOauthEmail($customerEmail);
    }
}
