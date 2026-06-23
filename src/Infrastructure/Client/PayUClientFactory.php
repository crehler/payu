<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Infrastructure\Client;

use Crehler\PaymentBundle\Infrastructure\Client\AbstractGatewayClientFactory;
use Crehler\PaymentBundle\Shared\EnhancedLogger;
use Crehler\PayU\Infrastructure\Exception\ConfigurationException;
use OauthGrantType;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Throwable;

/**
 * Reads PayU credentials per sales channel (sandbox or production set) and
 * initializes the OpenPayU SDK configuration on the shared PayUClient. Keeps the
 * config-key plumbing in the bundle base (requireString/isSandbox) so the adapter
 * no longer talks to SystemConfigService directly.
 */
final class PayUClientFactory extends AbstractGatewayClientFactory
{
    private const PREFIX = 'CrehlerPayU.config.';
    private const SANDBOX = 'sandbox';

    private const POS_ID = 'posId';
    private const MD5_KEY = 'md5Key';
    private const CLIENT_ID = 'clientId';
    private const CLIENT_SECRET = 'clientSecret';

    private const SANDBOX_POS_ID = 'sandboxPosId';
    private const SANDBOX_MD5_KEY = 'sandboxMd5Key';
    private const SANDBOX_CLIENT_ID = 'sandboxClientId';
    private const SANDBOX_CLIENT_SECRET = 'sandboxClientSecret';

    private const ENVIRONMENT_SANDBOX = 'sandbox';
    private const ENVIRONMENT_SECURE = 'secure';

    public function __construct(
        SystemConfigService $systemConfigService,
        private readonly PayUClient $payUClient,
        private readonly EnhancedLogger $logger,
    ) {
        parent::__construct($systemConfigService);
    }

    public function isSandboxEnabled(?string $salesChannelId = null): bool
    {
        return $this->isSandbox(self::PREFIX . self::SANDBOX, $salesChannelId);
    }

    /**
     * Read the active credential set and push it into the OpenPayU SDK.
     *
     * @throws ConfigurationException
     */
    public function create(?string $salesChannelId = null): PayUClient
    {
        $sandbox = $this->isSandboxEnabled($salesChannelId);

        [$posIdKey, $md5KeyKey, $clientIdKey, $clientSecretKey, $environment] = match ($sandbox) {
            true => [
                self::SANDBOX_POS_ID,
                self::SANDBOX_MD5_KEY,
                self::SANDBOX_CLIENT_ID,
                self::SANDBOX_CLIENT_SECRET,
                self::ENVIRONMENT_SANDBOX,
            ],
            false => [
                self::POS_ID,
                self::MD5_KEY,
                self::CLIENT_ID,
                self::CLIENT_SECRET,
                self::ENVIRONMENT_SECURE,
            ],
        };

        $posId = $this->requireString(self::PREFIX . $posIdKey, $salesChannelId);
        $md5Key = $this->requireString(self::PREFIX . $md5KeyKey, $salesChannelId);
        $clientId = $this->requireString(self::PREFIX . $clientIdKey, $salesChannelId);
        $clientSecret = $this->requireString(self::PREFIX . $clientSecretKey, $salesChannelId);

        try {
            $this->payUClient->initializeConfiguration(
                environment: $environment,
                grantType: OauthGrantType::CLIENT_CREDENTIAL,
                posId: $posId,
                signatureKey: $md5Key,
                oauthClientId: $clientId,
                oauthClientSecret: $clientSecret
            );
        } catch (Throwable $e) {
            $this->logger->error('PayU configuration error', ['exception' => $e]);

            throw new ConfigurationException('PayU configuration error: ' . $e->getMessage());
        }

        return $this->payUClient;
    }
}
