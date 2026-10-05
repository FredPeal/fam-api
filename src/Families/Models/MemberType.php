<?php

declare(strict_types=1);

namespace Fam\Families\Models;

use Fam\Families\Enums\MemberTypeName;
use Illuminate\Database\Eloquent\Model;

class MemberType extends Model
{
    protected $table = 'member_types';

    protected $guarded = [];

    /**
     * Find the member type for the given name, creating it if the table was never seeded.
     */
    public static function named(MemberTypeName $name): self
    {
        return static::query()->firstOrCreate(
            ['name' => $name->value],
            ['description' => $name->description()],
        );
    }
}
