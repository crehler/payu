<?php

declare(strict_types=1);

/**
 * @copyright 2026 Crehler Sp. z o.o.
 * @link https://crehler.com/
 * @license proprietary
 * support@crehler.com
 */

namespace Crehler\PayU\Domain\Exception;

use Crehler\PaymentBundle\Domain\Exception\DomainException;

final class OrderIdMissing extends DomainException
{
    public function __construct(string $message = 'Order id missing.')
    {
        parent::__construct($message);
    }
}
