<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Connection;

use Crehler\PaymentBundle\Application\DTO\Connection\ConnectionCheckResult;
use Crehler\PaymentBundle\Infrastructure\Connection\AbstractGatewayConnectionChecker;
use Crehler\PayU\Infrastructure\Client\PayUClient;
use OauthGrantType;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Throwable;

/**
 * Verifies PayU credentials by configuring the OpenPayU SDK with the values the
 * operator typed in the form (falling back to stored config for untouched fields)
 * and listing pay methods — an authenticated call that fails fast on a bad OAuth
 * client id/secret or pos id. Reflects the unsaved state of whichever environment's
 * card the button sits in.
 */
final class PayUConnectionChecker extends AbstractGatewayConnectionChecker
{
    private const CONFIG_DOMAIN = 'CrehlerPayU.config';
    private const ENVIRONMENT_SANDBOX = 'sandbox';
    private const ENVIRONMENT_SECURE = 'secure';

    public function __construct(
        SystemConfigService $systemConfigService,
        private readonly PayUClient $payUClient,
    ) {
        parent::__construct($systemConfigService);
    }

    public function check(string $environment, array $config, ?string $salesChannelId): ConnectionCheckResult
    {
        $sandbox = $environment === 'sandbox';

        $posId = $this->resolveValue($config, $sandbox ? 'sandboxPosId' : 'posId', $salesChannelId);
        $md5Key = $this->resolveValue($config, $sandbox ? 'sandboxMd5Key' : 'md5Key', $salesChannelId);
        $clientId = $this->resolveValue($config, $sandbox ? 'sandboxClientId' : 'clientId', $salesChannelId);
        $clientSecret = $this->resolveValue($config, $sandbox ? 'sandboxClientSecret' : 'clientSecret', $salesChannelId);

        if ($posId === '' || $md5Key === '' || $clientId === '' || $clientSecret === '') {
            return ConnectionCheckResult::failure('PosId, MD5 key, OAuth client id and secret are required.');
        }

        try {
            $this->payUClient->initializeConfiguration(
                environment: $sandbox ? self::ENVIRONMENT_SANDBOX : self::ENVIRONMENT_SECURE,
                grantType: OauthGrantType::CLIENT_CREDENTIAL,
                posId: $posId,
                signatureKey: $md5Key,
                oauthClientId: $clientId,
                oauthClientSecret: $clientSecret,
            );

            // Authenticated probe — an invalid OAuth client/pos id throws here.
            $this->payUClient->retrievePayMethods('pl');
        } catch (Throwable $e) {
            return ConnectionCheckResult::failure($e->getMessage());
        }

        return ConnectionCheckResult::ok('Connection successful.');
    }

    protected function configDomain(): string
    {
        return self::CONFIG_DOMAIN;
    }
}
