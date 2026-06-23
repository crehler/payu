<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Infrastructure\Resolver;

use Crehler\PayU\Application\DTO\CreateOrder\OrderResponse;
use Crehler\PayU\Application\Port\Driven\PaymentStatusResolverPort;
use Exception;
use OpenPayU_Result;
use Symfony\Component\HttpFoundation\Response;

use function in_array;

final class PaymentStatusResolver implements PaymentStatusResolverPort
{
    /**
     * @var string
     */
    public const SUCCESS = 'SUCCESS';
    /**
     * @var string
     */
    public const WARNING_CONTINUE = 'WARNING_CONTINUE_3DS';

    public function resolve(OpenPayU_Result $response, string $orderId): OrderResponse
    {
        if (!in_array($response->getStatus(), [self::SUCCESS, self::WARNING_CONTINUE], true)) {
            throw new Exception("Problem with creating new order {$response->getResponse()->orderId}
                    error: {$response->getError()}");
        }

        return new OrderResponse(
            status: true,
            code: Response::HTTP_OK,
            orderId: $response->getResponse()->orderId,
            redirectUri: $response->getResponse()->redirectUri ?? null
        );
    }
}
