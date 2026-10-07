<?php

declare(strict_types=1);

namespace Fam\Merchants\Exceptions;

use Exception;
use GraphQL\Error\ClientAware;

/**
 * Raised when a merchant cannot be stored. Safe to show to the client.
 */
class MerchantException extends Exception implements ClientAware
{
    public static function duplicateCode(string $code): self
    {
        return new self("A merchant with the code \"{$code}\" already exists.");
    }

    public function isClientSafe(): bool
    {
        return true;
    }
}
