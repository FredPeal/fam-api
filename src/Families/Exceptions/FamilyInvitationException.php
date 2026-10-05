<?php

declare(strict_types=1);

namespace Fam\Families\Exceptions;

use Exception;
use GraphQL\Error\ClientAware;

/**
 * Raised when an invitation cannot be accepted. Safe to show to the client.
 */
class FamilyInvitationException extends Exception implements ClientAware
{
    public static function alreadyOwner(): self
    {
        return new self('You already own this family.');
    }

    public static function alreadyMember(): self
    {
        return new self('You are already a member of this family.');
    }

    public function isClientSafe(): bool
    {
        return true;
    }
}
