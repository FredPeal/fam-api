<?php

declare(strict_types=1);

namespace Fam\Families\DataTransferObject;

use App\Models\User;
use Spatie\LaravelData\Data;

class Family extends Data
{
    public function __construct(
        public User $user,
        public string $name,
        public ?string $address = null,
    ) {}
}
