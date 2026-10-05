<?php

declare(strict_types=1);

namespace Fam\Families\Enums;

enum MemberTypeName: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    public function description(): string
    {
        return match ($this) {
            self::Owner => 'Created the family and has full control over it.',
            self::Admin => 'Can manage members and shared resources.',
            self::Member => 'Can view and use the family shared resources.',
        };
    }
}
