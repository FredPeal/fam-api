<?php

declare(strict_types=1);

namespace Fam\Merchants\DataTransferObject;

use Fam\Categories\Models\Category;
use Spatie\LaravelData\Data;

class Merchant extends Data
{
    public function __construct(
        public Category $category,
        public string $name,
        public ?string $code = null,
        public ?string $description = null,
    ) {}
}
