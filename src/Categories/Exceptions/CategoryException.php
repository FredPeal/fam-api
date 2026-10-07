<?php

declare(strict_types=1);

namespace Fam\Categories\Exceptions;

use Exception;
use GraphQL\Error\ClientAware;

/**
 * Raised when a category cannot be stored or shared. Safe to show to the client.
 */
class CategoryException extends Exception implements ClientAware
{
    public static function duplicateName(string $name): self
    {
        return new self("You already have a category named \"{$name}\".");
    }

    public static function alreadySharedWithFamily(): self
    {
        return new self('This category is already shared with the family.');
    }

    public function isClientSafe(): bool
    {
        return true;
    }
}
