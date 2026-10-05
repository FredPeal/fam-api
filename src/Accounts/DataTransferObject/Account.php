<?php

declare(strict_types=1);

namespace Fam\Accounts\DataTransferObject;

use App\Models\User;
use Fam\Accounts\Enums\AccountTypeId;
use Fam\Currencies\Models\Currency;
use Spatie\LaravelData\Data;

class Account extends Data
{
    public function __construct(
        public User $user,
        public string $name,
        public AccountTypeId $accountTypeId,
        public Currency $currency,
        public ?string $description = null,
        public ?string $icon = null,
        public float $initialBalance = 0.0,
    ) {}
}
