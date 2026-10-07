<?php

declare(strict_types=1);

namespace Fam\Transactions\Exceptions;

use Exception;
use GraphQL\Error\ClientAware;

/**
 * Raised when a transaction cannot be stored. Safe to show to the client.
 */
class TransactionException extends Exception implements ClientAware
{
    public static function zeroAmount(): self
    {
        return new self('A transaction amount cannot be zero.');
    }

    public function isClientSafe(): bool
    {
        return true;
    }
}
