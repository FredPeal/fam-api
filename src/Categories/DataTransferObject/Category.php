<?php

declare(strict_types=1);

namespace Fam\Categories\DataTransferObject;

use App\Models\User;
use Spatie\LaravelData\Data;

class Category extends Data
{
    public function __construct(
        public User $user,
        public string $name,
        public ?string $description = null,
        public ?string $icon = null,
    ) {}
}
